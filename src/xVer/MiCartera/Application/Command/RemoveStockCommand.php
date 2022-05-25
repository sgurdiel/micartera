<?php

namespace xVer\MiCartera\Application\Command;

use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryInterface;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

class RemoveStockCommand
{
    public function execute(StockRepositoryInterface $repo, Stock $stock, TransactionRepositoryInterface $transRepo): void
    {
        $repo->remove($stock, $transRepo);
    }
}
