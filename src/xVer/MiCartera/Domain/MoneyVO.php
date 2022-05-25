<?php

namespace xVer\MiCartera\Domain;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Currency\Currency;

class MoneyVO
{
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
        return number_format(floatval($this->value), $this->currency->getDecimals(), '.', '');
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    private function validValue(): void
    {
        $d = intval('1e'.$this->currency->getDecimals());
        if (false === filter_var($this->value, FILTER_VALIDATE_FLOAT, ['options' => ['decimal' => '.']])
            || 0 < bcmod(bcmul($this->value, (string) $d, 1), '1.0', 1) // Ensure precision does not exceed $this->currency->getDecimals()
        ) {
            throw new DomainException(
                new TranslationVO(
                    'moneyFormat',
                    ['precision' => $this->currency->getDecimals()],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'value'
            );
        }
    }

    public function add(MoneyVO $aux): MoneyVO
    {
        $this->assertSameCurrency($aux);
        return new self(NumberOperation::add($this->getCurrency()->getDecimals(), $this->value, $aux->getValue()), $this->currency);
    }

    public function subtract(MoneyVO $aux): MoneyVO
    {
        $this->assertSameCurrency($aux);
        return new self(NumberOperation::subtract($this->getCurrency()->getDecimals(), $this->value, $aux->getValue()), $this->currency);
    }

    public function percentageDifference(MoneyVO $aux, int $decimals = 2): string
    {
        $this->assertSameCurrency($aux);
        return NumberOperation::percentageDifference($decimals, $this->value, $aux->value);
    }

    public function same(MoneyVO $aux): bool
    {
        $this->assertSameCurrency($aux);
        return NumberOperation::same($this->getCurrency()->getDecimals(), $this->value, $aux->getValue());
    }

    /**
     * @phpstan-param string $aux
     * @psalm-param numeric-string $aux
     */
    public function multiply(string $aux): MoneyVO
    {
        return new self(NumberOperation::multiply($this->getCurrency()->getDecimals(), $this->value, $aux), $this->currency);
    }

    private function assertSameCurrency(MoneyVO $aux): void
    {
        if ($this->getCurrency()->getIso3() !== $aux->getCurrency()->getIso3()) {
            throw new DomainException(
                new TranslationVO(
                    'moneyOperationRequiresBothOperandsSameCurrency',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                )
            );
        }
    }
}
