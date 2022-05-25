<?php

namespace xVer\MiCartera\Domain\Stock\Transaction;

use xVer\Bundle\DomainBundle\Domain\EntityObjectsCollection;

/**
 * @template-extends EntityObjectsCollection<Liquidation>
 */
class LiquidationsCollection extends EntityObjectsCollection
{
    public function type(): string
    {
        return Liquidation::class;
    }
}
