<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\Stock;

interface StockRepositoryTestInterface
{
    public function testStockIsAddedUpdatedAndRemoved(): void;

    public function testFindByCurrency(): void;

    public function testRemovingStockHavingTransactionsThrowsException(): void;

    public function testAddingStockWithExistingCodeThrowsException(): void;
}