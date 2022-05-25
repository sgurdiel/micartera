<?php

namespace xVer\MiCartera\Infrastructure\Currency;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Currency\Currency;

trait CurrencyRepositoryTrait
{
    public function add(Currency $currency): Currency
    {
        $this->createConstraints($currency);
        $this->emPersist($currency);
        $this->emFlush();

        return $currency;
    }

    public function createConstraints(Currency $currency): void
    {
        if (null !== $this->findById($currency->getIso3())) {
            throw new DomainException(
                new TranslationVO(
                    'currencyExists',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'email'
            );
        }
    }
}
