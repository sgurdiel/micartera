<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock\Accounting;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Stock\Accounting\SummaryDTO;

/**
 * @covers xVer\MiCartera\Domain\Stock\Accounting\SummaryDTO
 */
class SummaryDTOTest extends TestCase
{
    public function testSummaryDTO(): void
    {
        $summaryDTO = new SummaryDTO('1.00', '2.00', '3.00', '4.00');
        $this->assertSame('1.00', $summaryDTO->acquisitionsPrice);
        $this->assertSame('2.00', $summaryDTO->acquisitionsExpenses);
        $this->assertSame('3.00', $summaryDTO->liquidationsPrice);
        $this->assertSame('4.00', $summaryDTO->liquidationsExpenses);
    }
}
