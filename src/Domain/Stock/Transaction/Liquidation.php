<?php

namespace xVer\MiCartera\Domain\Stock\Transaction;

use DateTime;
use InvalidArgumentException;
use Throwable;
use Doctrine\Common\Collections\Collection;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Accounting\Movement;
use xVer\MiCartera\Domain\Stock\Accounting\MovementRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Accounting\MovementsCollection;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;

class Liquidation extends TransactionAbstract implements EntityObjectInterface
{
    /** @var MovementsCollection */
    private Collection $movementsCollection;
    private int $amountRemaining;

    public function __construct(
        EntityObjectRepositoryLoaderInterface $repoLoader,
        Stock $stock,
        DateTime $datetimeutc,
        int $amount,
        MoneyVO $expenses,
        Account $account
    ) {
        parent::__construct($stock, $datetimeutc, $amount, $expenses, $account);
        $this->amountRemaining = $this->amount;
        $this->movementsCollection = new MovementsCollection([]);
        $this->persistCreate($repoLoader);
    }

    public function sameId(EntityObjectInterface $otherEntityObject): bool
    {
        if (!$otherEntityObject instanceof Liquidation) {
            throw new InvalidArgumentException();
        }
        return parent::getId()->equals($otherEntityObject->getId());
    }

    public function getAmountRemaining(): int
    {
        return $this->amountRemaining;
    }

    private function decreaseAmountRemaining(int $delta): void
    {
        $this->amountRemaining -= $delta;
        if (0 > $this->amountRemaining) {
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

    public function clearMovementsCollection(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): AcquisitionsCollection {
        $repoMovement = $repoLoader->load(MovementRepositoryInterface::class);
        $updatedAcquisitionsCollection = new AcquisitionsCollection([]);
        foreach ($this->movementsCollection->toArray() as $movement) {
            $acquisition = $movement->getAcquisition();
            $acquisition->unaccountMovement(
                $repoLoader,
                $movement
            );
            if (false === $updatedAcquisitionsCollection->contains($acquisition)) {
                $updatedAcquisitionsCollection->add($acquisition);
            }
            $repoMovement->remove($movement);
            $repoMovement->flush();
            parent::setExpensesUnaccountedFor($movement->getLiquidationExpenses(), false);
        }
        $this->movementsCollection->clear();
        $this->amountRemaining = $this->amount;
        $repoLoader->load(LiquidationRepositoryInterface::class)->persist($this);

        return $updatedAcquisitionsCollection;
    }

    public function accountMovement(
        EntityObjectRepositoryLoaderInterface $repoLoader,
        Movement $movement
    ): self {
        if (false === $this->sameId($movement->getLiquidation())) {
            throw new InvalidArgumentException();
        }
        parent::setExpensesUnaccountedFor($movement->getLiquidationExpenses(), true);
        $this->decreaseAmountRemaining($movement->getAmount());
        $this->movementsCollection->add($movement);
        $repoLoader->load(LiquidationRepositoryInterface::class)->persist($this);

        return $this;
    }

    protected function persistCreate(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): void {
        $repoLiquidation = $repoLoader->load(LiquidationRepositoryInterface::class);
        if (
            false === $repoLiquidation->assertNoTransWithSameAccountStockOnDateTime(
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
                )
            );
        }
        $repoLiquidation->beginTransaction();
        try {
            $this->fiFoCriteriaInstance($repoLoader)->onLiquidation($this);
            $repoLiquidation->persist($this);
            $repoLiquidation->flush();
            $repoLiquidation->commit();
        } catch (Throwable $th) {
            $repoLiquidation->rollBack();
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
        $repoLiquidation = $repoLoader->load(LiquidationRepositoryInterface::class);
        $repoLiquidation->beginTransaction();
        try {
            $this->fiFoCriteriaInstance($repoLoader)->onLiquidationRemoval($this);
            $repoLiquidation->remove($this);
            $repoLiquidation->flush();
            $repoLiquidation->commit();
        } catch (Throwable $th) {
            $repoLiquidation->rollBack();
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
}
