<?php

namespace xVer\MiCartera\Application\Query;

use xVer\Bundle\DomainBundle\Application\Query\QueryResponse;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

class TransactionQuery
{
    public function execute(
        TransactionRepositoryInterface $repo,
        Account $account,
        ?string $sortField,
        ?string $sortDir,
        ?int $limit = 10,
        int $page = 0
    ): QueryResponse {
        $page = $page ?: 0;
        $records = $repo->findByAccount($account, (is_null($limit) ? null : ($limit+1)), (is_null($limit) ? 0 : ($page*$limit)), $sortField, $sortDir);
        return new QueryResponse($records, $limit, $page);
    }
}
