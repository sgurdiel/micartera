<?php

namespace xVer\MiCartera\Domain\Accounting;

use DateTime;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\MoneyVO;

class SummaryVO
{
    private readonly int $yearOfFirstLiquidation;

    /**
     * @param numeric-string $allTimeAdquisitionsPrice,
     * @param numeric-string $allTimeAdquisitionsExpenses,
     * @param numeric-string $allTimeLiquidationsPrice,
     * @param numeric-string $allTimeLiquidationsExpenses,
     * @param numeric-string $displayedYearAdquisitionsPrice,
     * @param numeric-string $displayedYearAdquisitionsExpenses,
     * @param numeric-string $displayedYearLiquidationsPrice,
     * @param numeric-string $displayedYearLiquidationsExpenses
     */
    public function __construct(
        private readonly Account $account,
        private readonly int $displayedYear,
        readonly ?DateTime $dateTimeFirstLiquidationUtc,
        private readonly string $allTimeAdquisitionsPrice,
        private readonly string $allTimeAdquisitionsExpenses,
        private readonly string $allTimeLiquidationsPrice,
        private readonly string $allTimeLiquidationsExpenses,
        private readonly string $displayedYearAdquisitionsPrice,
        private readonly string $displayedYearAdquisitionsExpenses,
        private readonly string $displayedYearLiquidationsPrice,
        private readonly string $displayedYearLiquidationsExpenses
    ) {
        if (false === is_null($dateTimeFirstLiquidationUtc)) {
            $this->yearOfFirstLiquidation = (int) $dateTimeFirstLiquidationUtc->setTimezone($this->account->getTimeZone())
            ->format('Y');
        } else {
            $this->yearOfFirstLiquidation = (int) (new DateTime('now', $this->account->getTimeZone()))
            ->format('Y');
        }
    }

    public function getYearFirstLiquidation(): int
    {
        return $this->yearOfFirstLiquidation;
    }

    public function getAllTimeAdquisitionsPrice(): MoneyVO
    {
        return new MoneyVO($this->allTimeAdquisitionsPrice, $this->account->getCurrency());
    }

    public function getAllTimeAdquisitionsExpenses(): MoneyVO
    {
        return new MoneyVO($this->allTimeAdquisitionsExpenses, $this->account->getCurrency());
    }

    public function getAllTimeLiquidationsPrice(): MoneyVO
    {
        return new MoneyVO($this->allTimeLiquidationsPrice, $this->account->getCurrency());
    }

    public function getAllTimeLiquidationsExpenses(): MoneyVO
    {
        return new MoneyVO($this->allTimeLiquidationsExpenses, $this->account->getCurrency());
    }

    public function getAllTimeProfitPrice(): MoneyVO
    {
        return $this->getAllTimeLiquidationsPrice()->subtract(
            $this->getAllTimeAdquisitionsPrice()->add(
                $this->getAllTimeAdquisitionsExpenses()->add(
                    $this->getAllTimeLiquidationsExpenses()
                )
            )
        );
    }

    public function getAllTimeProfitPercentage(): string
    {
        return $this->getAllTimeAdquisitionsPrice()->percentageDifference(
            $this->getAllTimeLiquidationsPrice()->subtract(
                $this->getAllTimeAdquisitionsExpenses()->add(
                    $this->getAllTimeLiquidationsExpenses()
                )
            )
        );
    }

    public function getDisplayedYearAdquisitionsPrice(): MoneyVO
    {
        return new MoneyVO($this->displayedYearAdquisitionsPrice, $this->account->getCurrency());
    }

    public function getDisplayedYearAdquisitionsExpenses(): MoneyVO
    {
        return new MoneyVO($this->displayedYearAdquisitionsExpenses, $this->account->getCurrency());
    }

    public function getDisplayedYearLiquidationsPrice(): MoneyVO
    {
        return new MoneyVO($this->displayedYearLiquidationsPrice, $this->account->getCurrency());
    }

    public function getDisplayedYearLiquidationsExpenses(): MoneyVO
    {
        return new MoneyVO($this->displayedYearLiquidationsExpenses, $this->account->getCurrency());
    }

    public function getDisplayedYearProfitPrice(): MoneyVO
    {
        return $this->getDisplayedYearLiquidationsPrice()->subtract(
            $this->getDisplayedYearAdquisitionsPrice()->add(
                $this->getDisplayedYearAdquisitionsExpenses()->add(
                    $this->getDisplayedYearLiquidationsExpenses()
                )
            )
        );
    }

    public function getDisplayedYearProfitPercentage(): string
    {
        return $this->getDisplayedYearAdquisitionsPrice()->percentageDifference(
            $this->getDisplayedYearLiquidationsPrice()->subtract(
                $this->getDisplayedYearAdquisitionsExpenses()->add(
                    $this->getDisplayedYearLiquidationsExpenses()
                )
            )
        );
    }
}
