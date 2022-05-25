<?php

namespace xVer\MiCartera\Domain\Stock\Transaction;

use xVer\Bundle\DomainBundle\Domain\EntityObjectsCollection;

/**
 * @template-extends EntityObjectsCollection<Adquisition>
 */
class AdquisitionsCollection extends EntityObjectsCollection
{
    public function type(): string
    {
        return Adquisition::class;
    }
}
