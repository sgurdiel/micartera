<?php

namespace xVer\MiCartera\Application\Command;

use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryInterface;

class UpdateStockCommand
{
    public function execute(StockRepositoryInterface $repo, Stock $stock): void
    {
        $repo->update($stock);
    }
}
