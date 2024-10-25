<?php

namespace xVer\MiCartera\Domain\Stock\Accounting;

use xVer\Bundle\DomainBundle\Domain\EntityObjectsCollection;

/**
 * @template-extends EntityObjectsCollection<Movement>
 */
class MovementsCollection extends EntityObjectsCollection
{
    public function type(): string
    {
        return Movement::class;
    }
}
