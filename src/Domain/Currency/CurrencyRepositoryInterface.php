<?php

namespace xVer\MiCartera\Domain\Currency;

use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryInterface;
use xVer\MiCartera\Domain\Currency\CurrenciesCollection;
use xVer\MiCartera\Domain\Currency\Currency;

interface CurrencyRepositoryInterface extends EntityObjectRepositoryInterface
{
    public function persist(Currency $currency): Currency;

    public function findById(string $iso3): ?Currency;

    public function findByIdOrThrowException(string $iso3): Currency;

    public function all(): CurrenciesCollection;
}
