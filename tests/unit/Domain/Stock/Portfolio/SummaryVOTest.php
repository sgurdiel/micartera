<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock\Portfolio;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Portfolio\SummaryVO;

/**
 * @covers xVer\MiCartera\Domain\Stock\Portfolio\SummaryVO
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 */
class SummaryVOTest extends TestCase
{
    /** @dataProvider createValues */
    public function testSummaryVO(
        string $totalAcquisitionsPrice,
        string $totalAcquisitionsPriceInMoney,
        string $totalAcquisitionsExpenses,
        string $totalMarketsPrice,
        string $totalMarketsPriceInMoney,
        string $totalProfitPrice,
        string $totalProfitPercentage
    ): void {
        /** @var Currency&MockObject */
        $currency = $this->createStub(Currency::class);
        $currency->method('getDecimals')->willReturn(2);
        $portfolio = new SummaryVO(
            new MoneyVO($totalAcquisitionsPrice, $currency),
            new MoneyVO($totalAcquisitionsExpenses, $currency),
            new MoneyVO($totalMarketsPrice, $currency),
            $currency
        );
        $this->assertSame($totalAcquisitionsPriceInMoney, $portfolio->getTotalAcquisitionsPrice()->getValue());
        $this->assertSame($totalMarketsPriceInMoney, $portfolio->getTotalMarketsPrice()->getValue());
        $this->assertSame($totalAcquisitionsExpenses, $portfolio->getTotalAcquisitionsExpenses()->getValue());
        $this->assertSame($totalProfitPrice, $portfolio->getTotalProfitForecastPrice()->getValue());
        $this->assertSame($totalProfitPercentage, $portfolio->getTotalProfitForecastPercentage());
        $this->assertSame($currency, $portfolio->getCurrency());
    }

    public static function createValues(): array
    {
        return [
            ['0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00'],
            ['5666.3455', '5666.34', '46.21', '6773.8799', '6773.87', '1061.32', '18.73'],
        ];
    }
}
