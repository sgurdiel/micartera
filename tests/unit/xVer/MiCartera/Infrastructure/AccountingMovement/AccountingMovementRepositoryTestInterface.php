<?php

namespace Tests\unit\xVer\MiCartera\Infrastructure\AccountingMovement;

interface AccountingMovementRepositoryTestInterface
{
    public function testAddDoesNotStoreInDB(): void;

    public function testIsFoundBySellId(): void;

    public function testNotFoundByAccountReturnsEmptyArray(): void;

    public function testNotFoundBySellIdReturnsEmptyArray(): void;

    public function testIsFoundByBuyAndSellIds(): void;

    public function testNotFoundByBuyAndSellIdsReturnsNull(): void;

    //public function testAlreadyExistsThrowsException(): void;

    public function testIsFoundByAccountAndYear(): void;

    public function testNotFoundByAccountAndYearReturnsEmptyArray(): void;

    public function testFindYearOfOldestMovementByAccount(): void;

    public function testFindYearOfOldestMovementByAccountWithNoMovementsReturnsCurrentYear(): void;

    public function testFindTotalPurchaseAndSaleByAccount(): void;
}