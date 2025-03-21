<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock\Accounting;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Accounting\SummaryDTO;
use xVer\MiCartera\Domain\Stock\Accounting\SummaryVO;
use xVer\MiCartera\Domain\Currency\Currency;

/**
 * @covers xVer\MiCartera\Domain\Stock\Accounting\SummaryVO
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 */
class SummaryVOTest extends TestCase
{
    /** @dataProvider createValues */
    public function testSummaryVO(
        ?DateTime $dateTimeFirstLiquidationUtc,
        int $yearFirstLiquidation,
        SummaryDTO $allTimeSummaryDTO,
        string $allTimeProfitPrice,
        string $allTimeProfitPercentage,
        SummaryDTO $displayedYearSummaryDTO,
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
        $accountingMovement = new SummaryVO($account, $dateTimeFirstLiquidationUtc, $allTimeSummaryDTO, $displayedYearSummaryDTO);
        $this->assertSame($yearFirstLiquidation, $accountingMovement->getYearFirstLiquidation());
        $this->assertSame($allTimeSummaryDTO->acquisitionsPrice, $accountingMovement->getAllTimeAcquisitionsPrice()->getValue());
        $this->assertSame($allTimeSummaryDTO->acquisitionsExpenses, $accountingMovement->getAllTimeAcquisitionsExpenses()->getValue());
        $this->assertSame($allTimeSummaryDTO->liquidationsPrice, $accountingMovement->getAllTimeLiquidationsPrice()->getValue());
        $this->assertSame($allTimeSummaryDTO->liquidationsExpenses, $accountingMovement->getAllTimeLiquidationsExpenses()->getValue());
        $this->assertSame($allTimeProfitPrice, $accountingMovement->getAllTimeProfitPrice()->getValue());
        $this->assertSame($allTimeProfitPercentage, $accountingMovement->getAllTimeProfitPercentage());
        $this->assertSame($displayedYearSummaryDTO->acquisitionsPrice, $accountingMovement->getDisplayedYearAcquisitionsPrice()->getValue());
        $this->assertSame($displayedYearSummaryDTO->acquisitionsExpenses, $accountingMovement->getDisplayedYearAcquisitionsExpenses()->getValue());
        $this->assertSame($displayedYearSummaryDTO->liquidationsPrice, $accountingMovement->getDisplayedYearLiquidationsPrice()->getValue());
        $this->assertSame($displayedYearSummaryDTO->liquidationsExpenses, $accountingMovement->getDisplayedYearLiquidationsExpenses()->getValue());
        $this->assertSame($displayedYearProfitPrice, $accountingMovement->getDisplayedYearProfitPrice()->getValue());
        $this->assertSame($displayedYearProfitPercentage, $accountingMovement->getDisplayedYearProfitPercentage());
    }

    public static function createValues(): array
    {
        $dateNow = new DateTime('now', new DateTimeZone('UTC'));
        $dateThreeYearsAgo = new DateTime('3 years ago', new DateTimeZone('UTC'));
        return [
            [null, (int) $dateNow->format('Y'),
            new SummaryDTO('0.00', '0.00', '0.00', '0.00'), '0.00', '0.00',
            new SummaryDTO('0.00', '0.00', '0.00', '0.00'), '0.00', '0.00'],
            [$dateThreeYearsAgo, (int) $dateThreeYearsAgo->format('Y'),
            new SummaryDTO('450.00', '11.34', '1013.23', '8.05'), '543.84', '120.85',
            new SummaryDTO('0.00', '0.00', '0.00', '0.00'), '0.00', '0.00'],
            [$dateThreeYearsAgo, (int) $dateThreeYearsAgo->format('Y'),
            new SummaryDTO('450.00', '11.34', '1013.23', '8.05'), '543.84', '120.85',
            new SummaryDTO('450.00', '11.34', '1013.23', '8.05'), '543.84', '120.85']
        ];
    }
}
