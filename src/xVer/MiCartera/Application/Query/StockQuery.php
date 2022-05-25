<?php

namespace xVer\MiCartera\Application\Query;

use xVer\Bundle\DomainBundle\Application\Query\QueryResponse;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryInterface;

class StockQuery
{
    public function execute(
        StockRepositoryInterface $repo,
        Currency $currency,
        int $limit = 10,
        int $page = 0,
        string $sortField = 'code',
        string $sortDir = 'ASC'
    ): QueryResponse {
        $page = $page ?: 0;
        $records = $repo->findByCurrencySorted($currency, $limit+1, $page*$limit, $sortField, $sortDir);
        return new QueryResponse($records, $limit, $page);
    }
}
