<?php

namespace xVer\MiCartera\Application\Command;

use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInterface;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

class AddTransactionCommand
{
    public function execute(TransactionRepositoryInterface $repo, Transaction $transaction, AccountingMovementRepositoryInterface $accountingMovementRepo): Transaction
    {
        $transaction = $repo->add($transaction, $accountingMovementRepo);

        return $transaction;
    }
}
