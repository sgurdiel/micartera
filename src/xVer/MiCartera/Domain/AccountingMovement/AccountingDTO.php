<?php

namespace xVer\MiCartera\Domain\AccountingMovement;

use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;

class AccountingDTO
{
    private MoneyVO $yearPurchasePrice;
    private MoneyVO $yearSoldPrice;
    private MoneyVO $yearProfitPrice;
    private string $yearProfitPercentage;
    private int $currentYear;
    /** @var StockPriceVO[] */
    private array $purchasePrice = [];
    /** @var StockPriceVO[] */
    private array $soldPrice = [];
    /** @var MoneyVO[] */
    private array $profitPrice = [];
    /** @var string[] */
    private array $profitPercentage = [];
    private MoneyVO $totalPurchasePrice;
    private MoneyVO $totalSoldPrice;
    private MoneyVO $totalProfitPrice;
    private string $totalProfitPercentage;

    /**
     * @param AccountingMovement[] $accountingMovements
     * @param array<string|null> $totals
     */
    public function __construct(private Account $account, private int $year, private int $oldestYear, private array $accountingMovements, array $totals)
    {
        $this->loopAccountingMovements();
        $dateTime = new \DateTime('now', $this->account->getTimeZone());
        $this->currentYear = (int) $dateTime->format('Y');
        /** @psalm-var numeric-string */
        $auxTotalPurchasePrice = number_format((float) (is_null($totals['buy']) ? '0' : $totals['buy']), $this->account->getCurrency()->getDecimals(), '.', '');
        /** @psalm-var numeric-string */
        $auxTotalSoldPrice = number_format((float) (is_null($totals['sell']) ? '0' : $totals['sell']), $this->account->getCurrency()->getDecimals(), '.', '');
        $this->totalPurchasePrice = new MoneyVO($auxTotalPurchasePrice, $this->account->getCurrency());
        $this->totalSoldPrice = new MoneyVO($auxTotalSoldPrice, $this->account->getCurrency());
        $this->totalProfitPrice = $this->totalSoldPrice->subtract($this->totalPurchasePrice);
        $this->totalProfitPercentage = $this->totalPurchasePrice->percentageDifference($this->totalSoldPrice);
    }

    private function loopAccountingMovements(): void
    {
        $this->yearPurchasePrice = new MoneyVO('0', $this->account->getCurrency());
        $this->yearSoldPrice = new MoneyVO('0', $this->account->getCurrency());
        $this->yearProfitPrice = new MoneyVO('0', $this->account->getCurrency());
        $this->yearProfitPercentage = '0.00';
        foreach ($this->accountingMovements as $key => $accountingMovement) {
            if (!($accountingMovement instanceof AccountingMovement)) {
                throw new \InvalidArgumentException();
            }
            $this->yearPurchasePrice = $this->yearPurchasePrice->add(
                $accountingMovement->getBuyTransaction()->getPrice()->multiply((string) $accountingMovement->getAmount())->toMoney()
            );
            $this->yearSoldPrice = $this->yearSoldPrice->add(
                $accountingMovement->getSellTransaction()->getPrice()->multiply((string) $accountingMovement->getAmount())->toMoney()
            );
            $this->yearProfitPrice = $this->yearSoldPrice->subtract($this->yearPurchasePrice);
            $this->yearProfitPercentage = $this->yearPurchasePrice->percentageDifference($this->yearSoldPrice);
            $this->purchasePrice[$key] = StockPriceVO::instantiate($accountingMovement->getBuyTransaction()->getPrice()->getValue(), $this->account->getCurrency())->multiply((string) $accountingMovement->getAmount());
            $this->soldPrice[$key] = StockPriceVO::instantiate($accountingMovement->getSellTransaction()->getPrice()->getValue(), $this->account->getCurrency())->multiply((string) $accountingMovement->getAmount());
            $this->profitPrice[$key] = $this->soldPrice[$key]->toMoney()->subtract($this->purchasePrice[$key]->toMoney());
            $this->profitPercentage[$key] = $this->purchasePrice[$key]->toMoney()->percentageDifference($this->soldPrice[$key]->toMoney());
        }
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * @return AccountingMovement[]
     */
    public function getAccountingMovements(): array
    {
        return $this->accountingMovements;
    }

    public function getYearPurchasePrice(): MoneyVO
    {
        return $this->yearPurchasePrice;
    }

    public function getYearSoldPrice(): MoneyVO
    {
        return $this->yearSoldPrice;
    }

    public function getYearForecastProfitPrice(): MoneyVO
    {
        return $this->yearProfitPrice;
    }

    public function getYearForecastProfitPercentage(): string
    {
        return $this->yearProfitPercentage;
    }

    public function getOldestYear(): int
    {
        return $this->oldestYear;
    }

    public function getCurrentYear(): int
    {
        return $this->currentYear;
    }

    public function getDisplayedYear(): int
    {
        return $this->year;
    }

    public function getPurchasePrice(int $key): ?StockPriceVO
    {
        return (isset($this->purchasePrice[$key]) ? $this->purchasePrice[$key] : null);
    }

    public function getSoldPrice(int $key): ?StockPriceVO
    {
        return (isset($this->soldPrice[$key]) ? $this->soldPrice[$key] : null);
    }

    public function getProfitPrice(int $key): ?MoneyVO
    {
        return (isset($this->profitPrice[$key]) ? $this->profitPrice[$key] : null);
    }

    public function getProfitPercentage(int $key): ?string
    {
        return (isset($this->profitPercentage[$key]) ? $this->profitPercentage[$key] : null);
    }

    public function getTotalPurchasePrice(): MoneyVO
    {
        return $this->totalPurchasePrice;
    }

    public function getTotalSoldPrice(): MoneyVO
    {
        return $this->totalSoldPrice;
    }

    public function getTotalProfitPrice(): MoneyVO
    {
        return $this->totalProfitPrice;
    }

    public function getTotalProfitPercentage(): string
    {
        return $this->totalProfitPercentage;
    }
}
