<?php

namespace xVer\MiCartera\Application\Command;

use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInterface;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

class RemoveTransactionCommand
{
    public function execute(TransactionRepositoryInterface $repo, Transaction $transaction, AccountingMovementRepositoryInterface $accountingMovementRepo): void
    {
        $repo->remove($transaction, $accountingMovementRepo);
    }
}
