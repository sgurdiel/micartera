<?php

namespace xVer\MiCartera\Application\Command;

use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

class UpdateTransactionCommand
{
    public function execute(TransactionRepositoryInterface $repo, Transaction $transaction): void
    {
        $repo->update($transaction);
    }
}
