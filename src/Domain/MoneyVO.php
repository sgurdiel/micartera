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
    public function __construct(private string $value, private readonly Currency $currency)
    {
        $this->validValue();
    }

    /**
     * @phpstan-return string
     * @psalm-return numeric-string
     * this operation is guaranteed to pruduce a numeric-string, but inference can't understand it
     * @psalm-suppress LessSpecificReturnStatement
     * this operation is guaranteed to pruduce a numeric-string, but inference can't understand it
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getValue(): string
    {
        return number_format(floatval($this->value), $this->getCurrency()->getDecimals(), '.', '');
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    private function validValue(): void
    {
        NumberOperation::validValue($this->value);
        $d = intval('1e' . $this->currency->getDecimals());
        $this->value = NumberOperation::divide(
            $this->currency->getDecimals(),
            NumberOperation::multiply(
                0,
                $this->value,
                (string) $d
            ),
            (string) $d
        );
    }

    public function add(MoneyVO $aux): MoneyVO
    {
        $this->assertSameCurrency($aux);
        return new self(
            NumberOperation::add(
                $this->getCurrency()->getDecimals(),
                $this->getValue(),
                $aux->getValue()
            ),
            $this->getCurrency()
        );
    }

    public function subtract(MoneyVO $aux): MoneyVO
    {
        $this->assertSameCurrency($aux);
        return new self(
            NumberOperation::subtract(
                $this->getCurrency()->getDecimals(),
                $this->getValue(),
                $aux->getValue()
            ),
            $this->getCurrency()
        );
    }

    public function percentageDifference(MoneyVO $aux, int $decimals = 2): string
    {
        $this->assertSameCurrency($aux);
        return NumberOperation::percentageDifference(
            $this->getCurrency()->getDecimals(),
            $decimals,
            $this->getValue(),
            $aux->getValue()
        );
    }

    public function same(MoneyVO $aux): bool
    {
        $this->assertSameCurrency($aux);
        return NumberOperation::same($this->getCurrency()->getDecimals(), $this->getValue(), $aux->getValue());
    }

    /**
     * @phpstan-param string $aux
     * @psalm-param numeric-string $aux
     */
    public function multiply(string $aux): MoneyVO
    {
        return new self(
            NumberOperation::multiply(
                $this->getCurrency()->getDecimals(),
                $this->getValue(),
                $aux
            ),
            $this->getCurrency()
        );
    }

    /**
     * @phpstan-param string $aux
     * @psalm-param numeric-string $aux
     */
    public function divide(string $aux): MoneyVO
    {
        return new self(
            NumberOperation::divide(
                $this->getCurrency()->getDecimals(),
                $this->getValue(),
                $aux
            ),
            $this->getCurrency()
        );
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
