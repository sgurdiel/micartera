<?php

namespace xVer\MiCartera\Infrastructure\Currency;

use xVer\Bundle\DomainBundle\Infrastructure\RepositoryInterface;
use xVer\MiCartera\Domain\Currency\Currency;

interface CurrencyRepositoryInterface extends RepositoryInterface
{
    public function add(Currency $currency): Currency;

    public function findById(string $iso3): ?Currency;

    public function createConstraints(Currency $currency): void;
}
