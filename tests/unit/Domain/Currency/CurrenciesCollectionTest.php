<?php declare(strict_types=1);

namespace Tests\unit\Domain\Currency;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Currency\CurrenciesCollection;
use xVer\MiCartera\Domain\Currency\Currency;

/**
 * @covers xVer\MiCartera\Domain\Currency\CurrenciesCollection
 */
class CurrenciesCollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $currenciesCollection = new CurrenciesCollection([]);
        $this->assertSame(Currency::class, $currenciesCollection->type());
    }
}
