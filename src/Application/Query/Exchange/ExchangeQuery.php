<?php

namespace xVer\MiCartera\Application\Query\Exchange;

use xVer\Bundle\DomainBundle\Application\AbstractApplication;
use xVer\Bundle\DomainBundle\Application\Query\EntityObjectsCollectionQueryResponse;
use xVer\MiCartera\Domain\Exchange\ExchangeRepositoryInterface;

class ExchangeQuery extends AbstractApplication
{
    public function all(): EntityObjectsCollectionQueryResponse
    {
        return new EntityObjectsCollectionQueryResponse(
            $this->repoLoader->load(ExchangeRepositoryInterface::class)->all()
        );
    }
}
