<?php declare(strict_types=1);

namespace Tests\integration\Application\Command\Stock;

use DateTime;
use DateTimeZone;
use Tests\integration\IntegrationTestCase;
use xVer\MiCartera\Application\Command\Stock\StockOperateCommand;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO;
use xVer\MiCartera\Domain\Stock\Transaction\Acquisition;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;

/**
 * @covers xVer\MiCartera\Application\Command\Stock\StockOperateCommand
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Exchange\Exchange
 * @uses xVer\MiCartera\Domain\Stock\Accounting\Movement
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Acquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Criteria\FiFoCriteria
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Liquidation
 * @uses xVer\MiCartera\Domain\Stock\Transaction\LiquidationsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountOutstandingVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Accounting\MovementRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Exchange\ExchangeRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\AcquisitionRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine
 */
class StockOperateCommandTest extends IntegrationTestCase
{
    public function testPurchaseCommandSucceeds(): void
    {
        self::$loadFixtures = true;
        $command = new StockOperateCommand($this->repoLoader);
        $acquisition = $command->purchase(
            'CABK',
            new DateTime('yesterday', new DateTimeZone('UTC')),
            '100',
            '5.43',
            '6.57',
            'test@example.com'
        );
        $this->assertInstanceOf(Acquisition::class, $acquisition);
    }

    public function testSellCommandSucceeds(): void
    {
        $command = new StockOperateCommand($this->repoLoader);
        $liquidation = $command->sell(
            'CABK',
            new DateTime('yesterday', new DateTimeZone('UTC')),
            '10',
            '7.55',
            '4.33',
            'test@example.com'
        );
        $this->assertInstanceOf(Liquidation::class, $liquidation);
    }
}
