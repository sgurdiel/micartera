<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\Transaction;

interface TransactionRepositoryTestInterface
{
    public function testBuyTransactionIsCreatedAndUpdatedAndRemoved(): void;

    public function testTransactionsAreFoundByStockId(): void;

    public function testTransactionsAreFoundByAccount(): void;

    public function testFindByAccountWithNonExistentAccountReturnsEmptyArray(): void;

    public function testFindByStockIdWithNonExistentStockReturnsEmptyArray(): void;

    public function testMultipleTransWithSameAccountStockTypeOnDateTimeThrowsException(): void;

    public function testSellTransactionIsCreatedAndRemoved(): void;

    public function testSellTransactionWithNoAmountOutStandingThrowsException(): void;

    public function testBuyTransactionsAreFoundByAccountWithAmountOutstanding(): void;

    //public function testBuyTransactionRemovalWhenNotFullAmountOutstandingThrowsException(): void;
}