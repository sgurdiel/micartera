<?php

namespace xVer\MiCartera\Domain\Stock;

use xVer\Bundle\DomainBundle\Domain\EntityObjectsCollection;

/**
 * @template-extends EntityObjectsCollection<Stock>
 */
class StocksCollection extends EntityObjectsCollection
{
    public function type(): string
    {
        return Stock::class;
    }
}
