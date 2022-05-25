<?php declare(strict_types=1);

namespace Tests\unit\Domain\Portfolio;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Portfolio\SummaryVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;

/**
 * @covers xVer\MiCartera\Domain\Portfolio\SummaryVO
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 */
class SummaryVOTest extends TestCase
{
    /** @dataProvider createValues */
    public function testSummaryVO(
        string $totalAdquisitionsPrice,
        string $totalAdquisitionsPriceInMoney,
        string $totalAdquisitionsExpenses,
        string $totalMarketsPrice,
        string $totalMarketsPriceInMoney,
        string $totalProfitPrice,
        string $totalProfitPercentage
    ): void {
        /** @var Currency&MockObject */
        $currency = $this->createStub(Currency::class);
        $currency->method('getDecimals')->willReturn(2);
        $portfolio = new SummaryVO(
            new MoneyVO($totalAdquisitionsPrice, $currency),
            new MoneyVO($totalAdquisitionsExpenses, $currency),
            new MoneyVO($totalMarketsPrice, $currency)
        );
        $this->assertSame($totalAdquisitionsPriceInMoney, $portfolio->getTotalAdquisitionsPrice()->getValue());
        $this->assertSame($totalMarketsPriceInMoney, $portfolio->getTotalMarketsPrice()->getValue());
        $this->assertSame($totalAdquisitionsExpenses, $portfolio->getTotalAdquisitionsExpenses()->getValue());
        $this->assertSame($totalProfitPrice, $portfolio->getTotalProfitForecastPrice()->getValue());
        $this->assertSame($totalProfitPercentage, $portfolio->getTotalProfitForecastPercentage());
    }

    public static function createValues(): array
    {
        return [
            ['0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00'],
            ['5666.3455', '5666.34', '46.21', '6773.8799', '6773.87', '1061.32', '18.73'],
        ];
    }
}
