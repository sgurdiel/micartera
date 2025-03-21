<?php

namespace xVer\MiCartera\Domain\Stock;

use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Number\Number;
use xVer\MiCartera\Domain\Number\NumberInterface;
use xVer\MiCartera\Domain\Number\NumberOperation;

class StockPriceVO extends Number
{
    final public const LOWEST_PRICE = '0';
    final public const HIGHEST_PRICE = '999999.9999';
    private NumberOperation $numberOperation;

    /**
     * @psalm-param numeric-string $value
     */
    public function __construct(string $value, private readonly Currency $currency)
    {
        parent::__construct($value, true, self::LOWEST_PRICE, self::HIGHEST_PRICE);
        $this->numberOperation = new NumberOperation();
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function multiply(NumberInterface $operand): StockPriceVO
    {
        return new self(
            $this->numberOperation->multiply(
                $this->getValueMaxDecimals(),
                $this,
                $operand
            ),
            $this->currency
        );
    }

    public function toMoney(): MoneyVO
    {
        return new MoneyVO(
            $this->getValue(),
            $this->currency
        );
    }
}
