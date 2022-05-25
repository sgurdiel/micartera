<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock\Transaction;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Stock\Transaction\Adquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationsCollection;

/**
 * @covers xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection
 * @covers xVer\MiCartera\Domain\Stock\Transaction\LiquidationsCollection
 */
class TransactionsCollectionTest extends TestCase
{
    public function testAdquisitionCollection(): void
    {
        $collection = new AdquisitionsCollection([]);
        $this->assertSame(Adquisition::class, $collection->type());
    }

    public function testLiquidationCollection(): void
    {
        $collection = new LiquidationsCollection([]);
        $this->assertSame(Liquidation::class, $collection->type());
    }
}
