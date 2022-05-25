<?php

namespace xVer\MiCartera\Domain\Transaction;

use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;

class PortfolioDTO
{
    private MoneyVO $purchasePrice;
    private MoneyVO $currentPrice;
    private MoneyVO $profitPrice;
    private string $profitPercentage;
    /** @var StockPriceVO[] */
    private array $transOutstandingPurchasePrice = [];
    /** @var StockPriceVO[] */
    private array $transOutstandingCurrentPrice = [];
    /** @var MoneyVO[] */
    private array $transOutstandingProfitPrice = [];
    /** @var string[] */
    private array $transOutstandingProfitPercentage = [];

    /**
     * @param Transaction[] $outstandingPositions
     */
    public function __construct(private Account $account, private array $outstandingPositions)
    {
        $this->loopOutstandingPositions();
    }

    /**
     * @psalm-suppress DocblockTypeContradiction
     */
    private function loopOutstandingPositions(): void
    {
        $this->purchasePrice = new MoneyVO('0', $this->account->getCurrency());
        $this->currentPrice = new MoneyVO('0', $this->account->getCurrency());
        foreach ($this->outstandingPositions as $key => $outstandingPosition) {
            if (
                !($outstandingPosition instanceof Transaction)
                || Transaction::TYPE_SELL === $outstandingPosition->getType()
                || 0 >= $outstandingPosition->getAmountOutstanding()
            ) {
                throw new \InvalidArgumentException();
            }

            $this->transOutstandingPurchasePrice[$key] = StockPriceVO::instantiate(
                $outstandingPosition->getPrice()->getValue(),
                $outstandingPosition->getCurrency()
            )->multiply(
                (string) $outstandingPosition->getAmountOutstanding()
            );
            $this->transOutstandingCurrentPrice[$key] = StockPriceVO::instantiate(
                $outstandingPosition->getStock()->getPrice()->getValue(),
                $outstandingPosition->getCurrency()
            )->multiply(
                (string) $outstandingPosition->getAmountOutstanding()
            );
            $this->transOutstandingProfitPrice[$key] = $this->transOutstandingCurrentPrice[$key]->toMoney()->subtract($this->transOutstandingPurchasePrice[$key]->toMoney());
            $this->transOutstandingProfitPercentage[$key] = $this->transOutstandingPurchasePrice[$key]->toMoney()->percentageDifference($this->transOutstandingCurrentPrice[$key]->toMoney());

            $this->purchasePrice = $this->purchasePrice->add(
                $outstandingPosition->getPrice()->multiply(
                    (string) $outstandingPosition->getAmountOutstanding()
                )->toMoney()
            );
            $this->currentPrice = $this->currentPrice->add(
                $outstandingPosition->getStock()->getPrice()->multiply(
                    (string) $outstandingPosition->getAmountOutstanding()
                )->toMoney()
            );
        }
        $this->profitPrice = $this->currentPrice->subtract($this->purchasePrice);
        $this->profitPercentage = $this->purchasePrice->percentageDifference($this->currentPrice);
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * @return Transaction[]
     */
    public function getOutstandingPositions(): array
    {
        return $this->outstandingPositions;
    }

    public function getPurchasePrice(): MoneyVO
    {
        return $this->purchasePrice;
    }

    public function getCurrentPrice(): MoneyVO
    {
        return $this->currentPrice;
    }

    public function getProfitForecastPrice(): MoneyVO
    {
        return $this->profitPrice;
    }

    public function getProfitForecastPercentage(): string
    {
        return $this->profitPercentage;
    }

    public function getTransOutstandingPurchasePrice(int $key): ?StockPriceVO
    {
        return (isset($this->transOutstandingPurchasePrice[$key]) ? $this->transOutstandingPurchasePrice[$key] : null);
    }

    public function getTransOutstandingCurrentPrice(int $key): ?StockPriceVO
    {
        return (isset($this->transOutstandingCurrentPrice[$key]) ? $this->transOutstandingCurrentPrice[$key] : null);
    }

    public function getTransOutstandingProfitPrice(int $key): ?MoneyVO
    {
        return (isset($this->transOutstandingProfitPrice[$key]) ? $this->transOutstandingProfitPrice[$key] : null);
    }

    public function getTransOutstandingProfitPercentage(int $key): ?string
    {
        return (isset($this->transOutstandingProfitPercentage[$key]) ? $this->transOutstandingProfitPercentage[$key] : null);
    }
}
