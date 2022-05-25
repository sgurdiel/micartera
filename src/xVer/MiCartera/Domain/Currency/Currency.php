<?php

namespace xVer\MiCartera\Domain\Currency;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;

class Currency implements EntityInterface
{
    public function __construct(private string $iso3, private string $symbol, private int $decimals)
    {
        if (3 !== strlen($this->iso3)) {
            throw new DomainException(
                new TranslationVO(
                    'invalidIso3',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'iso3'
            );
        }
        if (0 >= strlen($this->symbol)) {
            throw new DomainException(
                new TranslationVO(
                    'invalidSymbol',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'symbol'
            );
        }
        if (0 >= $this->decimals || 4 < $this->decimals) {
            throw new DomainException(
                new TranslationVO(
                    'numberBetween',
                    ['minimum' => '1', 'maximum' => '4'],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'amount'
            );
        }
        $this->iso3 = strtoupper($this->iso3);
    }

    public function getIso3(): string
    {
        return $this->iso3;
    }

    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof Currency) {
            throw new \InvalidArgumentException();
        }
        return (0 === strcmp($this->getIso3(), $otherEntity->getIso3()));
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }
}
