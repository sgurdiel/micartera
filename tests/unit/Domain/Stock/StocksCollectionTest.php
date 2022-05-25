<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StocksCollection;

/**
 * @covers xVer\MiCartera\Domain\Stock\StocksCollection
 */
class StocksCollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $stocksCollection = new StocksCollection([]);
        $this->assertSame(Stock::class, $stocksCollection->type());
    }
}
