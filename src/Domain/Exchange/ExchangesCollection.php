<?php

namespace xVer\MiCartera\Domain\Exchange;

use xVer\Bundle\DomainBundle\Domain\EntityObjectsCollection;

/**
 * @template-extends EntityObjectsCollection<Exchange>
 */
class ExchangesCollection extends EntityObjectsCollection
{
    public function type(): string
    {
        return Exchange::class;
    }
}
