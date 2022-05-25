<?php

namespace xVer\MiCartera\Application\Query;

use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\AccountingMovement\AccountingDTO;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInterface;

class AccountingQuery
{
    public function execute(
        AccountingMovementRepositoryInterface $repo,
        Account $account,
        int $year,
    ): AccountingDTO {
        return new AccountingDTO(
            $account,
            $year,
            $repo->findYearOfOldestMovementByAccount($account),
            $repo->findByAccountAndYear($account, $year, 0, null),
            $repo->findTotalPurchaseAndSaleByAccount($account)
        );
    }
}
