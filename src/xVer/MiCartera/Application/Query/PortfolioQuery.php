<?php

namespace xVer\MiCartera\Application\Query;

use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Transaction\PortfolioDTO;
use xVer\MiCartera\Infrastructure\SortOrderEnum;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;
use xVer\MiCartera\Infrastructure\Transaction\TransactionSortFieldEnum;

class PortfolioQuery
{
    public function execute(
        TransactionRepositoryInterface $repo,
        Account $account
    ): PortfolioDTO {
        return new PortfolioDTO($account, $repo->findBuyTransactionsByAccountWithAmountOutstanding($account, 'ASC', 'datetimeutc', null, 0));
    }
}
