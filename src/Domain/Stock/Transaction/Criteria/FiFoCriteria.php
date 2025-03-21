<?php

namespace xVer\MiCartera\Domain\Stock\Transaction\Criteria;

use DateTime;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Accounting\Movement;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\Acquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationsCollection;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountOutstandingVO;

class FiFoCriteria
{
    private AcquisitionsCollection $acquisitionsCollection;

    public function __construct(
        private readonly EntityObjectRepositoryLoaderInterface $repoLoader
    ) {
        $this->acquisitionsCollection = new AcquisitionsCollection([]);
    }

    public function onAcquisition(
        Acquisition $acquisition
    ): void {
        $this->acquisitionsCollection = new AcquisitionsCollection([]);

        $this->acquisitionsCollection->add($acquisition);

        $liquidationsCollection = $this->repoLoader->load(
            LiquidationRepositoryInterface::class
        )->findByAccountStockAndDateAtOrAfter(
            $acquisition->getAccount(),
            $acquisition->getStock(),
            $acquisition->getDateTimeUtc()
        );

        $this->traverseLiquidationsClearingMovements($liquidationsCollection);

        $this->sortAcquisitionsByOldestFirst();

        $this->traverseLiquidationsAccountingMovements($liquidationsCollection);
    }

    public function onLiquidation(
        Liquidation $liquidation
    ): void {
        $this->acquisitionsCollection = new AcquisitionsCollection([]);

        $liquidationsCollection = $this->repoLoader->load(
            LiquidationRepositoryInterface::class
        )->findByAccountStockAndDateAtOrAfter(
            $liquidation->getAccount(),
            $liquidation->getStock(),
            $liquidation->getDateTimeUtc()
        );

        $lastLiquidation = $liquidationsCollection->last();
        $this->includePersistedAcquisitionsWithAmountOutstanding(
            $liquidation->getAccount(),
            $liquidation->getStock(),
            (
                false !== $lastLiquidation
                ? $lastLiquidation->getDateTimeUtc()
                : $liquidation->getDateTimeUtc()
            )
        );

        $this->traverseLiquidationsClearingMovements($liquidationsCollection);

        $this->sortAcquisitionsByOldestFirst();

        $this->accountMovements($liquidation);

        $this->traverseLiquidationsAccountingMovements($liquidationsCollection);
    }

    public function onLiquidationRemoval(
        Liquidation $liquidation
    ): void {
        $this->acquisitionsCollection = new AcquisitionsCollection([]);

        $liquidationsCollection = $this->repoLoader->load(
            LiquidationRepositoryInterface::class
        )->findByAccountStockAndDateAtOrAfter(
            $liquidation->getAccount(),
            $liquidation->getStock(),
            $liquidation->getDateTimeUtc()
        );

        $this->traverseLiquidationsClearingMovements($liquidationsCollection);

        $this->sortAcquisitionsByOldestFirst();

        $liquidationsCollection->removeElement($liquidation);

        $this->traverseLiquidationsAccountingMovements($liquidationsCollection);
    }

    private function traverseLiquidationsClearingMovements(
        LiquidationsCollection $liquidationsCollection
    ): void {
        foreach ($liquidationsCollection->toArray() as $liquidation) {
            $this->mergeAcquisitions(
                $liquidation->clearMovementsCollection($this->repoLoader)
            );
        }
    }

    private function includePersistedAcquisitionsWithAmountOutstanding(
        Account $account,
        Stock $stock,
        DateTime $dateLastLiquidation
    ): void {
        $acquisitionsWithAmountOutstandingCollection = $this->repoLoader->load(
            AcquisitionRepositoryInterface::class
        )->findByAccountStockWithAmountOutstandingAndDateAtOrBefore(
            $account,
            $stock,
            $dateLastLiquidation
        );
        $this->mergeAcquisitions($acquisitionsWithAmountOutstandingCollection);
    }

    private function mergeAcquisitions(AcquisitionsCollection $acquisitionsCollection): void
    {
        foreach ($acquisitionsCollection->toArray() as $acquisition) {
            if (false === $this->acquisitionsCollection->contains($acquisition)) {
                $this->acquisitionsCollection->add($acquisition);
            }
        }
    }

    private function sortAcquisitionsByOldestFirst(): void
    {
        $acquisitionsArray = $this->acquisitionsCollection->toArray();
        usort($acquisitionsArray, fn (Acquisition $a, Acquisition $b) => $a->getDateTimeUtc() <=> $b->getDateTimeUtc());
        $this->acquisitionsCollection = new AcquisitionsCollection($acquisitionsArray);
    }

    private function traverseLiquidationsAccountingMovements(LiquidationsCollection $liquidationsCollection): void
    {
        foreach ($liquidationsCollection->toArray() as $liquidation) {
            $this->accountMovements($liquidation);
        }
    }

    private function accountMovements(Liquidation $liquidation): void
    {
        foreach ($this->acquisitionsCollection->toArray() as $acquisition) {
            if ($acquisition->getAmountOutstanding()->greater(new TransactionAmountOutstandingVO('0'))) {
                try {
                    new Movement($this->repoLoader, $acquisition, $liquidation);
                } catch (DomainException) {
                    throw new DomainException(
                        new TranslationVO('transNotPassFifoSpec', [], TranslationVO::DOMAIN_VALIDATORS)
                    );
                }
                if ($liquidation->getAmountRemaining()->same(new TransactionAmountOutstandingVO('0'))) {
                    break;
                }
            }
        }
        if (
            $liquidation->getAmountRemaining()->different(new TransactionAmountOutstandingVO('0'))
            ||
            $liquidation->getExpensesUnaccountedFor()->different(new TransactionAmountOutstandingVO('0'))
        ) {
            throw new DomainException(
                new TranslationVO('transNotPassFifoSpec', [], TranslationVO::DOMAIN_VALIDATORS)
            );
        }
    }
}
