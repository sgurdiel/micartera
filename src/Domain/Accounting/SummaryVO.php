<?php

namespace xVer\MiCartera\Domain\Accounting;

use DateTime;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\MoneyVO;

class SummaryVO
{
    private readonly int $yearOfFirstLiquidation;

    public function __construct(
        private readonly Account $account,
        private readonly int $displayedYear,
        readonly ?DateTime $dateTimeFirstLiquidationUtc,
        private readonly SummaryDTO $summaryAllTimeDTO,
        private readonly SummaryDTO $summaryDisplayedYearDTO
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
        return new MoneyVO($this->summaryAllTimeDTO->adquisitionsPrice, $this->account->getCurrency());
    }

    public function getAllTimeAdquisitionsExpenses(): MoneyVO
    {
        return new MoneyVO($this->summaryAllTimeDTO->adquisitionsExpenses, $this->account->getCurrency());
    }

    public function getAllTimeLiquidationsPrice(): MoneyVO
    {
        return new MoneyVO($this->summaryAllTimeDTO->liquidationsPrice, $this->account->getCurrency());
    }

    public function getAllTimeLiquidationsExpenses(): MoneyVO
    {
        return new MoneyVO($this->summaryAllTimeDTO->liquidationsExpenses, $this->account->getCurrency());
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
        return new MoneyVO($this->summaryDisplayedYearDTO->adquisitionsPrice, $this->account->getCurrency());
    }

    public function getDisplayedYearAdquisitionsExpenses(): MoneyVO
    {
        return new MoneyVO($this->summaryDisplayedYearDTO->adquisitionsExpenses, $this->account->getCurrency());
    }

    public function getDisplayedYearLiquidationsPrice(): MoneyVO
    {
        return new MoneyVO($this->summaryDisplayedYearDTO->liquidationsPrice, $this->account->getCurrency());
    }

    public function getDisplayedYearLiquidationsExpenses(): MoneyVO
    {
        return new MoneyVO($this->summaryDisplayedYearDTO->liquidationsExpenses, $this->account->getCurrency());
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
