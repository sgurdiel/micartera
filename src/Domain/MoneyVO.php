<?php

namespace xVer\MiCartera\Domain;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Number\Number;
use xVer\MiCartera\Domain\Number\NumberInterface;
use xVer\MiCartera\Domain\Number\NumberOperation;

class MoneyVO extends Number
{
    /** @var numeric-string */
    private string $lowestValue = '0';
    /** @var numeric-string */
    private string $highestValue = '0';
    private NumberOperation $numberOperation;

    /**
     * @psalm-param numeric-string $value
     */
    public function __construct(
        string $value,
        private readonly Currency $currency
    ) {
        $this->setValueLimits();
        parent::__construct($value, false, $this->getLowestValue(), $this->getHighestValue());
        $this->numberOperation = new NumberOperation();
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function add(MoneyVO $aux): MoneyVO
    {
        $this->assertSameCurrency($aux);
        return new self(
            $this->numberOperation->add(
                $this->getCurrency()->getDecimals(),
                $this,
                $aux
            ),
            $this->getCurrency()
        );
    }

    public function subtract(MoneyVO $aux): MoneyVO
    {
        $this->assertSameCurrency($aux);
        return new self(
            $this->numberOperation->subtract(
                $this->getCurrency()->getDecimals(),
                $this,
                $aux
            ),
            $this->getCurrency()
        );
    }

    public function percentageDifference(MoneyVO $aux, int $decimals = 2): string
    {
        $this->assertSameCurrency($aux);
        return $this->numberOperation->percentageDifference(
            $this->getCurrency()->getDecimals(),
            $decimals,
            $this,
            $aux
        );
    }

    public function multiply(NumberInterface $aux): MoneyVO
    {
        return new self(
            $this->numberOperation->multiply(
                $this->getCurrency()->getDecimals(),
                $this,
                $aux
            ),
            $this->getCurrency()
        );
    }

    public function divide(NumberInterface $aux): MoneyVO
    {
        return new self(
            $this->numberOperation->divide(
                $this->getCurrency()->getDecimals(),
                $this,
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

    private function setValueLimits(): void
    {
        $decimals = '';
        for ($i=0; $i < $this->getCurrency()->getDecimals(); $i++) {
            $decimals .= ($i === 0 ? '.' : '') . '9';
        }
        /** @var numeric-string */
        $this->lowestValue = '-999999999999' . $decimals;
        /** @var numeric-string */
        $this->highestValue = '999999999999' . $decimals;
    }

    /**
     * @return numeric-string
     */
    public function getLowestValue(): string
    {
        return $this->lowestValue;
    }

    /**
     * @return numeric-string
     */
    public function getHighestValue(): string
    {
        return $this->highestValue;
    }
}
