<?php

namespace xVer\MiCartera\Domain\Stock\Transaction\Criteria;

use DateTime;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Accounting\Movement;
use xVer\MiCartera\Domain\NumberOperation;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\Adquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationsCollection;

class FiFoCriteria
{
    private AdquisitionsCollection $adquisitionsCollection;

    public function __construct(
        private readonly EntityObjectRepositoryLoaderInterface $repoLoader
    ) {
        $this->adquisitionsCollection = new AdquisitionsCollection([]);
    }

    public function onAdquisition(
        Adquisition $adquisition
    ): void {
        $this->adquisitionsCollection = new AdquisitionsCollection([]);

        $this->adquisitionsCollection->add($adquisition);

        $liquidationsCollection = $this->repoLoader->load(
            LiquidationRepositoryInterface::class
        )->findByAccountStockAndDateAtOrAfter(
            $adquisition->getAccount(),
            $adquisition->getStock(),
            $adquisition->getDateTimeUtc()
        );

        $this->traverseLiquidationsClearingMovements($liquidationsCollection);

        $this->sortAdquisitionsByOldestFirst();

        $this->traverseLiquidationsAccountingMovements($liquidationsCollection);
    }

    public function onLiquidation(
        Liquidation $liquidation
    ): void {
        $this->adquisitionsCollection = new AdquisitionsCollection([]);

        $liquidationsCollection = $this->repoLoader->load(
            LiquidationRepositoryInterface::class
        )->findByAccountStockAndDateAtOrAfter(
            $liquidation->getAccount(),
            $liquidation->getStock(),
            $liquidation->getDateTimeUtc()
        );

        $lastLiquidation = $liquidationsCollection->last();
        $this->includePersistedAdquisitionsWithAmountOutstanding(
            $liquidation->getAccount(),
            $liquidation->getStock(),
            (
                false !== $lastLiquidation
                ? $lastLiquidation->getDateTimeUtc()
                : $liquidation->getDateTimeUtc()
            )
        );

        $this->traverseLiquidationsClearingMovements($liquidationsCollection);

        $this->sortAdquisitionsByOldestFirst();

        $this->accountMovements($liquidation);

        $this->traverseLiquidationsAccountingMovements($liquidationsCollection);
    }

    public function onLiquidationRemoval(
        Liquidation $liquidation
    ): void {
        $this->adquisitionsCollection = new AdquisitionsCollection([]);

        $liquidationsCollection = $this->repoLoader->load(
            LiquidationRepositoryInterface::class
        )->findByAccountStockAndDateAtOrAfter(
            $liquidation->getAccount(),
            $liquidation->getStock(),
            $liquidation->getDateTimeUtc()
        );

        $this->traverseLiquidationsClearingMovements($liquidationsCollection);

        $this->sortAdquisitionsByOldestFirst();

        $liquidationsCollection->removeElement($liquidation);

        $this->traverseLiquidationsAccountingMovements($liquidationsCollection);
    }

    private function traverseLiquidationsClearingMovements(
        LiquidationsCollection $liquidationsCollection
    ): void {
        foreach ($liquidationsCollection->toArray() as $liquidation) {
            $this->mergeAdquisitions(
                $liquidation->clearMovementsCollection($this->repoLoader)
            );
        }
    }

    private function includePersistedAdquisitionsWithAmountOutstanding(
        Account $account,
        Stock $stock,
        DateTime $dateLastLiquidation
    ): void {
        $adquisitionsWithAmountOutstandingCollection = $this->repoLoader->load(
            AdquisitionRepositoryInterface::class
        )->findByAccountStockWithAmountOutstandingAndDateAtOrBefore(
            $account,
            $stock,
            $dateLastLiquidation
        );
        $this->mergeAdquisitions($adquisitionsWithAmountOutstandingCollection);
    }

    private function mergeAdquisitions(AdquisitionsCollection $adquisitionsCollection): void
    {
        foreach ($adquisitionsCollection->toArray() as $adquisition) {
            if (false === $this->adquisitionsCollection->contains($adquisition)) {
                $this->adquisitionsCollection->add($adquisition);
            }
        }
    }

    private function sortAdquisitionsByOldestFirst(): void
    {
        $adquisitionsArray = $this->adquisitionsCollection->toArray();
        usort($adquisitionsArray, fn (Adquisition $a, Adquisition $b) => $a->getDateTimeUtc() <=> $b->getDateTimeUtc());
        $this->adquisitionsCollection = new AdquisitionsCollection($adquisitionsArray);
    }

    private function traverseLiquidationsAccountingMovements(LiquidationsCollection $liquidationsCollection): void
    {
        foreach ($liquidationsCollection->toArray() as $liquidation) {
            $this->accountMovements($liquidation);
        }
    }

    private function accountMovements(Liquidation $liquidation): void
    {
        foreach ($this->adquisitionsCollection->toArray() as $adquisition) {
            if (0 < $adquisition->getAmountOutstanding()) {
                try {
                    new Movement($this->repoLoader, $adquisition, $liquidation);
                } catch (DomainException) {
                    throw new DomainException(
                        new TranslationVO('transNotPassFifoSpec', [], TranslationVO::DOMAIN_VALIDATORS)
                    );
                }
                if (0 === $liquidation->getAmountRemaining()) {
                    break;
                }
            }
        }
        if (
            0 !== $liquidation->getAmountRemaining()
            ||
            0 !== NumberOperation::compare(
                $liquidation->getExpensesUnaccountedFor()->getCurrency()->getDecimals(),
                $liquidation->getExpensesUnaccountedFor()->getValue(),
                '0'
            )
        ) {
            throw new DomainException(
                new TranslationVO('transNotPassFifoSpec', [], TranslationVO::DOMAIN_VALIDATORS)
            );
        }
    }
}
