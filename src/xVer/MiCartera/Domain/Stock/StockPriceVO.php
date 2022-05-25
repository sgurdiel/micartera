<?php

namespace xVer\MiCartera\Domain\Stock;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\NumberOperation;

class StockPriceVO
{
    public const DECIMALS = 4;

    /**
     * @phpstan-param string $value
     * @psalm-param numeric-string $value
     */
    public function __construct(private string $value, private Currency $currency)
    {
        $this->validValue();
    }

    /**
     * @phpstan-param string $value
     * @psalm-param numeric-string $value
     */
    public static function instantiate(string $value, Currency $currency): self
    {
        return new self($value, $currency);
    }

    /**
     * @phpstan-return string
     * @psalm-return numeric-string
     * @psalm-suppress LessSpecificReturnStatement this operation is guaranteed to pruduce a numeric-string, but inference can't understand it
     * @psalm-suppress MoreSpecificReturnType      this operation is guaranteed to pruduce a numeric-string, but inference can't understand it
     */
    public function getValue(): string
    {
        return number_format(floatval($this->value), 4, '.', '');
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    private function validValue(): void
    {
        $d = intval('1e'.self::DECIMALS);
        if (false === filter_var($this->value, FILTER_VALIDATE_FLOAT, ['options' => ['decimal' => '.']])
            || 0 < bcmod(bcmul($this->value, (string) $d, 1), '1.0', 1) // Ensure precision does not exceed self::DECIMALS
            ||-1 === bccomp($this->value, "0", self::DECIMALS)
            || 1 === bccomp($this->value, "99999.9999", self::DECIMALS)
        ) {
            throw new DomainException(
                new TranslationVO(
                    'numberBetween',
                    ['minimum' => '0', 'maximum' => '99999.9999'],
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
        return new self(NumberOperation::multiply(4, $this->value, $aux), $this->currency);
    }

    public function toMoney(): MoneyVO
    {
        return new MoneyVO((string) round(floatval($this->value), $this->currency->getDecimals(), PHP_ROUND_HALF_UP), $this->currency);
    }
}
