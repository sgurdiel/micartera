<?php

namespace xVer\MiCartera\Application\Query\Currency;

use xVer\Bundle\DomainBundle\Application\AbstractApplication;
use xVer\Bundle\DomainBundle\Application\Query\EntityObjectsCollectionQueryResponse;
use xVer\MiCartera\Domain\Currency\CurrencyRepositoryInterface;

class CurrencyQuery extends AbstractApplication
{
    public function all(): EntityObjectsCollectionQueryResponse
    {
        return new EntityObjectsCollectionQueryResponse(
            $this->repoLoader->load(CurrencyRepositoryInterface::class)->all()
        );
    }
}
