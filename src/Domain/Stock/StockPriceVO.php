<?php

namespace xVer\MiCartera\Domain\Stock;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\NumberOperation;

class StockPriceVO
{
    final public const DECIMALS = 4;
    final public const HIGHEST_PRICE = '999999.9999';

    /**
     * @psalm-param numeric-string $value
     */
    public function __construct(private readonly string $value, private readonly Currency $currency)
    {
        $this->validValue();
    }

    /**
     * @psalm-return numeric-string
     * this operation is guaranteed to pruduce a numeric-string, but inference can't understand it
     * @psalm-suppress LessSpecificReturnStatement
     * this operation is guaranteed to pruduce a numeric-string, but inference can't understand it
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getValue(): string
    {
        return number_format(floatval($this->value), self::DECIMALS, '.', '');
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    private function validValue(): void
    {
        NumberOperation::validValue($this->value);
        NumberOperation::validPrecision($this->value, self::DECIMALS);
        if (
            1 === NumberOperation::compare(self::DECIMALS, '0', $this->value)
            ||
            1 === NumberOperation::compare(self::DECIMALS, $this->value, self::HIGHEST_PRICE)
        ) {
            throw new DomainException(
                new TranslationVO(
                    'stockPriceFormat',
                    ['minimum' => '0', 'maximum' => self::HIGHEST_PRICE],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'price'
            );
        }
    }

    /**
     * @phpstan-param string $aux
     * @psalm-param numeric-string $aux
     */
    public function multiply(string $aux): StockPriceVO
    {
        return new self(NumberOperation::multiply(self::DECIMALS, $this->value, $aux), $this->currency);
    }

    public function toMoney(): MoneyVO
    {
        $d = intval('1e' . $this->currency->getDecimals());
        $money = NumberOperation::divide(
            $this->currency->getDecimals(),
            NumberOperation::multiply(
                0,
                $this->value,
                (string) $d
            ),
            (string) $d
        );
        return new MoneyVO(
            $money,
            $this->currency
        );
    }
}
