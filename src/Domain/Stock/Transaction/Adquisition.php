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
use xVer\MiCartera\Domain\Accounting\Movement;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;

class Adquisition extends TransactionAbstract implements EntityObjectInterface
{
    private int $amountOutstanding = 0;

    public function __construct(
        EntityObjectRepositoryLoaderInterface $repoLoader,
        Stock $stock,
        DateTime $datetimeutc,
        int $amount,
        MoneyVO $expenses,
        Account $account
    ) {
        parent::__construct($stock, $datetimeutc, $amount, $expenses, $account);
        $this->setAmountOutstanding($this->amount, false);
        $this->persistCreate($repoLoader);
    }

    public function sameId(EntityObjectInterface $otherEntityObject): bool
    {
        if (!$otherEntityObject instanceof Adquisition) {
            throw new InvalidArgumentException();
        }
        return parent::getId()->equals($otherEntityObject->getId());
    }

    public function getAmountOutstanding(): int
    {
        return $this->amountOutstanding;
    }

    private function setAmountOutstanding(int $delta, bool $subtract): void
    {
        if ($subtract) {
            $this->amountOutstanding -= $delta;
        } else {
            $this->amountOutstanding += $delta;
        }

        if (
            0 > $this->amountOutstanding
            || $this->amount < $this->amountOutstanding
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
    }

    public function accountMovement(
        EntityObjectRepositoryLoaderInterface $repoLoader,
        Movement $movement
    ): self {
        if (false === $this->sameId($movement->getAdquisition())) {
            throw new InvalidArgumentException();
        }
        $this->setAmountOutstanding($movement->getAmount(), true);
        $this->setExpensesUnaccountedFor($movement->getAdquisitionExpenses(), true);
        $repoLoader->load(AdquisitionRepositoryInterface::class)->persist($this);

        return $this;
    }

    public function unaccountMovement(
        EntityObjectRepositoryLoaderInterface $repoLoader,
        Movement $movement
    ): self {
        if (false === $this->sameId($movement->getAdquisition())) {
            throw new InvalidArgumentException();
        }
        $this->setAmountOutstanding($movement->getAmount(), false);
        $this->setExpensesUnaccountedFor($movement->getAdquisitionExpenses(), false);
        $repoLoader->load(AdquisitionRepositoryInterface::class)->persist($this);

        return $this;
    }

    protected function persistCreate(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): void {
        $repoAdquisition = $repoLoader->load(AdquisitionRepositoryInterface::class);
        if (
            false === $repoAdquisition->assertNoTransWithSameAccountStockOnDateTime(
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
        $repoAdquisition->beginTransaction();
        try {
            $this->fiFoCriteriaInstance($repoLoader)->onAdquisition($this);
            $repoAdquisition->persist($this);
            $repoAdquisition->flush();
            $repoAdquisition->commit();
        } catch (Throwable $th) {
            $repoAdquisition->rollBack();
            if (is_a($th, DomainException::class)) {
                throw $th;
            } else {
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

    public function persistRemove(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): void {
        if ($this->getAmount() !== $this->getAmountOutstanding()) {
            throw new DomainException(
                new TranslationVO(
                    'transBuyCannotBeRemovedWithoutFullAmountOutstanding',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                )
            );
        }
        $repoAdquisition = $repoLoader->load(AdquisitionRepositoryInterface::class);
        try {
            $repoAdquisition->remove($this);
            $repoAdquisition->flush();
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
