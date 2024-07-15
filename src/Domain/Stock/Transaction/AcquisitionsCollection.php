<?php

namespace xVer\MiCartera\Domain\Stock\Transaction;

use xVer\Bundle\DomainBundle\Domain\EntityObjectsCollection;

/**
 * @template-extends EntityObjectsCollection<Acquisition>
 */
class AcquisitionsCollection extends EntityObjectsCollection
{
    public function type(): string
    {
        return Acquisition::class;
    }
}
