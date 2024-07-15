<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock\Transaction;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Stock\Transaction\Acquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationsCollection;

/**
 * @covers xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection
 * @covers xVer\MiCartera\Domain\Stock\Transaction\LiquidationsCollection
 */
class TransactionsCollectionTest extends TestCase
{
    public function testAcquisitionCollection(): void
    {
        $collection = new AcquisitionsCollection([]);
        $this->assertSame(Acquisition::class, $collection->type());
    }

    public function testLiquidationCollection(): void
    {
        $collection = new LiquidationsCollection([]);
        $this->assertSame(Liquidation::class, $collection->type());
    }
}
