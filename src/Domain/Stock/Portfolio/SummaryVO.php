<?php

namespace xVer\MiCartera\Domain\Stock\Portfolio;

use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;

class SummaryVO
{
    public function __construct(
        private readonly MoneyVO $totalAcquisitionsPrice,
        private readonly MoneyVO $totalAcquisitionsExpenses,
        private readonly MoneyVO $marketsPrice,
        private readonly Currency $currency
    ) {
    }

    public function getTotalAcquisitionsPrice(): MoneyVO
    {
        return $this->totalAcquisitionsPrice;
    }

    public function getTotalMarketsPrice(): MoneyVO
    {
        return $this->marketsPrice;
    }

    public function getTotalAcquisitionsExpenses(): MoneyVO
    {
        return $this->totalAcquisitionsExpenses;
    }

    public function getTotalProfitForecastPrice(): MoneyVO
    {
        return $this->getTotalMarketsPrice()->subtract(
            $this->getTotalAcquisitionsPrice()->add(
                $this->getTotalAcquisitionsExpenses()
            )
        );
    }

    public function getTotalProfitForecastPercentage(): string
    {
        return $this->getTotalAcquisitionsPrice()->percentageDifference(
            $this->getTotalMarketsPrice()->subtract(
                $this->getTotalAcquisitionsExpenses()
            )
        );
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }
}
