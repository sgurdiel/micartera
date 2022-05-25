<?php declare(strict_types=1);

namespace Tests\unit\Domain\Accounting;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Accounting\SummaryVO;
use xVer\MiCartera\Domain\Currency\Currency;

/**
 * @covers xVer\MiCartera\Domain\Accounting\SummaryVO
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 */
class SummaryVOTest extends TestCase
{
    /** @dataProvider createValues */
    public function testSummaryVO(
        int $displayedYear,
        ?DateTime $dateTimeFirstLiquidationUtc,
        int $yearFirstLiquidation,
        string $allTimeAdquisitionsPrice,
        string $allTimeAdquisitionsExpenses,
        string $allTimeLiquidationsPrice,
        string $allTimeLiquidationsExpenses,
        string $allTimeProfitPrice,
        string $allTimeProfitPercentage,
        string $displayedYearAdquisitionsPrice,
        string $displayedYearAdquisitionsExpenses,
        string $displayedYearLiquidationsPrice,
        string $displayedYearLiquidationsExpenses,
        string $displayedYearProfitPrice,
        string $displayedYearProfitPercentage
    ): void {
        /** @var Currency&MockObject */
        $currency = $this->createStub(Currency::class);
        $currency->method('getDecimals')->willReturn(2);
        /** @var Account&MockObject */
        $account = $this->createStub(Account::class);
        $account->method('getTimeZone')->willReturn(new DateTimeZone('UTC'));
        $account->method('getCurrency')->willReturn($currency);
        $accountingMovement = new SummaryVO($account, $displayedYear, $dateTimeFirstLiquidationUtc, $allTimeAdquisitionsPrice, $allTimeAdquisitionsExpenses, $allTimeLiquidationsPrice, $allTimeLiquidationsExpenses, $displayedYearAdquisitionsPrice, $displayedYearAdquisitionsExpenses, $displayedYearLiquidationsPrice, $displayedYearLiquidationsExpenses);
        $this->assertSame($yearFirstLiquidation, $accountingMovement->getYearFirstLiquidation());
        $this->assertSame($allTimeAdquisitionsPrice, $accountingMovement->getAllTimeAdquisitionsPrice()->getValue());
        $this->assertSame($allTimeAdquisitionsExpenses, $accountingMovement->getAllTimeAdquisitionsExpenses()->getValue());
        $this->assertSame($allTimeLiquidationsPrice, $accountingMovement->getAllTimeLiquidationsPrice()->getValue());
        $this->assertSame($allTimeLiquidationsExpenses, $accountingMovement->getAllTimeLiquidationsExpenses()->getValue());
        $this->assertSame($allTimeProfitPrice, $accountingMovement->getAllTimeProfitPrice()->getValue());
        $this->assertSame($allTimeProfitPercentage, $accountingMovement->getAllTimeProfitPercentage());
        $this->assertSame($displayedYearAdquisitionsPrice, $accountingMovement->getDisplayedYearAdquisitionsPrice()->getValue());
        $this->assertSame($displayedYearAdquisitionsExpenses, $accountingMovement->getDisplayedYearAdquisitionsExpenses()->getValue());
        $this->assertSame($displayedYearLiquidationsPrice, $accountingMovement->getDisplayedYearLiquidationsPrice()->getValue());
        $this->assertSame($displayedYearLiquidationsExpenses, $accountingMovement->getDisplayedYearLiquidationsExpenses()->getValue());
        $this->assertSame($displayedYearProfitPrice, $accountingMovement->getDisplayedYearProfitPrice()->getValue());
        $this->assertSame($displayedYearProfitPercentage, $accountingMovement->getDisplayedYearProfitPercentage());
    }

    public static function createValues(): array
    {
        $dateNow = new DateTime('now', new DateTimeZone('UTC'));
        $dateThreeYearsAgo = new DateTime('3 years ago', new DateTimeZone('UTC'));
        return [
            [(int) $dateNow->format('Y'), null, (int) $dateNow->format('Y'),
            '0.00', '0.00', '0.00', '0.00', '0.00', '0.00',
            '0.00', '0.00', '0.00', '0.00', '0.00', '0.00'],
            [(int) $dateThreeYearsAgo->format('Y'), $dateThreeYearsAgo, (int) $dateThreeYearsAgo->format('Y'),
            '450.00', '11.34', '1013.23', '8.05', '543.84', '120.85',
            '0.00', '0.00', '0.00', '0.00', '0.00', '0.00'],
            [(int) $dateThreeYearsAgo->format('Y'), $dateThreeYearsAgo, (int) $dateThreeYearsAgo->format('Y'),
            '450.00', '11.34', '1013.23', '8.05', '543.84', '120.85',
            '450.00', '11.34', '1013.23', '8.05', '543.84', '120.85']
        ];
    }
}
