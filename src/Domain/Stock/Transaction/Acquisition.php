<?php

namespace xVer\MiCartera\Domain\Stock\Transaction;

use DateTime;
use InvalidArgumentException;
use Throwable;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Accounting\Movement;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO;

class Acquisition extends TransactionAbstract implements EntityObjectInterface
{
    /** @var numeric-string */
    private string $amountOutstanding;

    public function __construct(
        EntityObjectRepositoryLoaderInterface $repoLoader,
        Stock $stock,
        DateTime $datetimeutc,
        TransactionAmountVO $amount,
        MoneyVO $expenses,
        Account $account
    ) {
        parent::__construct($stock, $datetimeutc, $amount, $expenses, $account);
        $this->amountOutstanding = (new TransactionAmountOutstandingVO('0'))->getValue();
        $this->setAmountOutstanding(new TransactionAmountOutstandingVO($amount->getValue()), false);
        $this->persistCreate($repoLoader);
    }

    public function sameId(EntityObjectInterface $otherEntityObject): bool
    {
        if (!$otherEntityObject instanceof Acquisition) {
            throw new InvalidArgumentException();
        }
        return parent::getId()->equals(
            $otherEntityObject->getId()
        );
    }

    public function getAmountOutstanding(): TransactionAmountOutstandingVO
    {
        return new TransactionAmountOutstandingVO($this->amountOutstanding);
    }

    private function setAmountOutstanding(TransactionAmountOutstandingVO $delta, bool $subtract): void
    {
        if (
            (
                $subtract
                &&
                $this->getAmountOutstanding()->smaller($delta)
            )
            ||
            (
                !$subtract
                &&
                $this->getAmount()->smaller(
                    $this->getAmountOutstanding()->add($delta)
                )
            )
        ) {
            throw new DomainException(
                new TranslationVO(
                    'MovementAmountNotWithinAllowedLimits',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }

        $subtract === true
        ?
            $this->amountOutstanding = $this->getAmountOutstanding()->subtract($delta)->getValue()
        :
            $this->amountOutstanding = $this->getAmountOutstanding()->add($delta)->getValue()
        ;
    }

    public function accountMovement(
        EntityObjectRepositoryLoaderInterface $repoLoader,
        Movement $movement
    ): self {
        if (false === $this->sameId($movement->getAcquisition())) {
            throw new InvalidArgumentException();
        }
        $this->setAmountOutstanding(new TransactionAmountOutstandingVO($movement->getAmount()->getValue()), true);
        $this->setExpensesUnaccountedFor($movement->getAcquisitionExpenses(), true);
        $repoLoader->load(AcquisitionRepositoryInterface::class)->persist($this);

        return $this;
    }

    public function unaccountMovement(
        EntityObjectRepositoryLoaderInterface $repoLoader,
        Movement $movement
    ): self {
        if (false === $this->sameId($movement->getAcquisition())) {
            throw new InvalidArgumentException();
        }
        $this->setAmountOutstanding(new TransactionAmountOutstandingVO($movement->getAmount()->getValue()), false);
        $this->setExpensesUnaccountedFor($movement->getAcquisitionExpenses(), false);
        $repoLoader->load(AcquisitionRepositoryInterface::class)->persist($this);

        return $this;
    }

    protected function persistCreate(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): void {
        $repoAcquisition = $repoLoader->load(AcquisitionRepositoryInterface::class);
        if (
            false === $repoAcquisition->assertNoTransWithSameAccountStockOnDateTime(
                $this->getAccount(),
                $this->getStock(),
                $this->getDateTimeUtc()
            )
        ) {
            throw new DomainException(
                new TranslationVO(
                    'transExistsOnDateTime',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
        $repoAcquisition->beginTransaction();
        try {
            $this->fiFoCriteriaInstance($repoLoader)->onAcquisition($this);
            $repoAcquisition->persist($this);
            $repoAcquisition->flush();
            $repoAcquisition->commit();
        } catch (Throwable $th) {
            $repoAcquisition->rollBack();
            if(is_a($th, DomainException::class)) {
                throw $th;
            }
            throw new DomainException(
                new TranslationVO(
                    'actionFailed',
                    [],
                    TranslationVO::DOMAIN_MESSAGES
                )
            );
        }
    }

    public function persistRemove(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): void {
        if ($this->getAmount()->different($this->getAmountOutstanding())) {
            throw new DomainException(
                new TranslationVO(
                    'transBuyCannotBeRemovedWithoutFullAmountOutstanding',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                )
            );
        }
        $repoAcquisition = $repoLoader->load(AcquisitionRepositoryInterface::class);
        try {
            $repoAcquisition->remove($this);
            $repoAcquisition->flush();
        } catch (Throwable) {
            throw new DomainException(
                new TranslationVO(
                    'actionFailed',
                    [],
                    TranslationVO::DOMAIN_MESSAGES
                )
            );
        }
    }
}
