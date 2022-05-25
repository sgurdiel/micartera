<?php declare(strict_types=1);

namespace Tests\unit\Domain\Accounting;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Accounting\Movement;
use xVer\MiCartera\Domain\Accounting\MovementsCollection;

/**
 * @covers xVer\MiCartera\Domain\Accounting\MovementsCollection
 */
class MovementsCollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $accountingMovementsCollection = new MovementsCollection([]);
        $this->assertSame(Movement::class, $accountingMovementsCollection->type());
    }
}
