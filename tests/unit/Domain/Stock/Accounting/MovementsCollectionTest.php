<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock\Accounting;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Stock\Accounting\Movement;
use xVer\MiCartera\Domain\Stock\Accounting\MovementsCollection;

/**
 * @covers xVer\MiCartera\Domain\Stock\Accounting\MovementsCollection
 */
class MovementsCollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $accountingMovementsCollection = new MovementsCollection([]);
        $this->assertSame(Movement::class, $accountingMovementsCollection->type());
    }
}
