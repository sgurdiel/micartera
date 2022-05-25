<?php

namespace xVer\MiCartera\Application\Command;

use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryInterface;

class AddStockCommand
{
    public function execute(StockRepositoryInterface $repo, Stock $stock): Stock
    {
        $stock = $repo->add($stock);

        return $stock;
    }
}
