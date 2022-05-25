<?php

namespace xVer\MiCartera\Infrastructure\Currency;

use xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory;
use xVer\MiCartera\Domain\Currency\Currency;

class CurrencyRepositoryInMemory extends PersistanceInMemory implements CurrencyRepositoryInterface
{
    use CurrencyRepositoryTrait;

    public function findById(string $iso3): ?Currency
    {
        /** @var Currency $currency */
        foreach ($this->getPersistedObjects() as $currency) {
            if (0 === strcmp($currency->getIso3(), strtoupper($iso3))) {
                return $currency;
            }
        }
        return null;
    }
}
