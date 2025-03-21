<?php

namespace xVer\MiCartera\Domain\Stock\Accounting;

use DateTime;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\MoneyVO;

class SummaryVO
{
    private readonly int $yearOfFirstLiquidation;

    public function __construct(
        private readonly Account $account,
        readonly ?DateTime $dateTimeFirstLiquidationUtc,
        private readonly SummaryDTO $summaryAllTimeDTO,
        private readonly SummaryDTO $summaryDisplayedYearDTO
    ) {
        false === is_null($dateTimeFirstLiquidationUtc) ?
            $this->yearOfFirstLiquidation = (int) $dateTimeFirstLiquidationUtc->setTimezone($this->account->getTimeZone())
            ->format('Y')
        :
            $this->yearOfFirstLiquidation = (int) (new DateTime('now', $this->account->getTimeZone()))
            ->format('Y')
        ;
    }

    public function getYearFirstLiquidation(): int
    {
        return $this->yearOfFirstLiquidation;
    }

    public function getAllTimeAcquisitionsPrice(): MoneyVO
    {
        return new MoneyVO($this->summaryAllTimeDTO->acquisitionsPrice, $this->account->getCurrency());
    }

    public function getAllTimeAcquisitionsExpenses(): MoneyVO
    {
        return new MoneyVO($this->summaryAllTimeDTO->acquisitionsExpenses, $this->account->getCurrency());
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
            $this->getAllTimeAcquisitionsPrice()->add(
                $this->getAllTimeAcquisitionsExpenses()->add(
                    $this->getAllTimeLiquidationsExpenses()
                )
            )
        );
    }

    public function getAllTimeProfitPercentage(): string
    {
        return $this->getAllTimeAcquisitionsPrice()->percentageDifference(
            $this->getAllTimeLiquidationsPrice()->subtract(
                $this->getAllTimeAcquisitionsExpenses()->add(
                    $this->getAllTimeLiquidationsExpenses()
                )
            )
        );
    }

    public function getDisplayedYearAcquisitionsPrice(): MoneyVO
    {
        return new MoneyVO($this->summaryDisplayedYearDTO->acquisitionsPrice, $this->account->getCurrency());
    }

    public function getDisplayedYearAcquisitionsExpenses(): MoneyVO
    {
        return new MoneyVO($this->summaryDisplayedYearDTO->acquisitionsExpenses, $this->account->getCurrency());
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
            $this->getDisplayedYearAcquisitionsPrice()->add(
                $this->getDisplayedYearAcquisitionsExpenses()->add(
                    $this->getDisplayedYearLiquidationsExpenses()
                )
            )
        );
    }

    public function getDisplayedYearProfitPercentage(): string
    {
        return $this->getDisplayedYearAcquisitionsPrice()->percentageDifference(
            $this->getDisplayedYearLiquidationsPrice()->subtract(
                $this->getDisplayedYearAcquisitionsExpenses()->add(
                    $this->getDisplayedYearLiquidationsExpenses()
                )
            )
        );
    }
}
