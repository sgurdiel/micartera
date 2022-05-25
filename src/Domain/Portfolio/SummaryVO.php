<?php

namespace xVer\MiCartera\Domain\Portfolio;

use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;

class SummaryVO
{
    public function __construct(
        private readonly MoneyVO $totalAdquisitionsPrice,
        private readonly MoneyVO $totalAdquisitionsExpenses,
        private readonly MoneyVO $marketsPrice
    ) {
    }

    public function getTotalAdquisitionsPrice(): MoneyVO
    {
        return $this->totalAdquisitionsPrice;
    }

    public function getTotalMarketsPrice(): MoneyVO
    {
        return $this->marketsPrice;
    }

    public function getTotalAdquisitionsExpenses(): MoneyVO
    {
        return $this->totalAdquisitionsExpenses;
    }

    public function getTotalProfitForecastPrice(): MoneyVO
    {
        return $this->getTotalMarketsPrice()->subtract(
            $this->getTotalAdquisitionsPrice()->add(
                $this->getTotalAdquisitionsExpenses()
            )
        );
    }

    public function getTotalProfitForecastPercentage(): string
    {
        return $this->getTotalAdquisitionsPrice()->percentageDifference(
            $this->getTotalMarketsPrice()->subtract(
                $this->getTotalAdquisitionsExpenses()
            )
        );
    }
}
