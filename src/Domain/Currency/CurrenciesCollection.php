<?php

namespace xVer\MiCartera\Domain\Currency;

use xVer\Bundle\DomainBundle\Domain\EntityObjectsCollection;

/**
  * @template-extends EntityObjectsCollection<Currency>
 */
class CurrenciesCollection extends EntityObjectsCollection
{
    public function type(): string
    {
        return Currency::class;
    }
}
