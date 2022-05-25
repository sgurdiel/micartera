<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\Currency;

use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInterface;

interface CurrencyRepositoryTestInterface
{
    public function testCurrencyIsAdded(): CurrencyRepositoryInterface;

    /** @depends testCurrencyIsAdded */
    public function testCurrencyIsFoundById(CurrencyRepositoryInterface $repo): CurrencyRepositoryInterface;

    /** @depends testCurrencyIsAdded */
    public function testAddingCurrencyWithExistingIso3ThrowsException(CurrencyRepositoryInterface $repo): void;
}