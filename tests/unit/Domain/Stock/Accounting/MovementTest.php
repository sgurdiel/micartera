<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock\Accounting;

use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Domain\Stock\Accounting\Movement;
use xVer\MiCartera\Domain\Stock\Accounting\MovementRepositoryInterface;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Stock\Transaction\Acquisition;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountOutstandingVO;

/**
 * @covers xVer\MiCartera\Domain\Stock\Accounting\Movement
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountOutstandingVO
 */
class MovementTest extends TestCase
{
    private EntityObjectRepositoryLoader&Stub $repoLoader;
    private Stock&Stub $stock;
    private MovementRepositoryInterface&MockObject $repoMovement;

    public function setUp(): void
    {
        /** @var MovementRepositoryInterface&MockObject */
        $this->repoMovement = $this->createMock(MovementRepositoryInterface::class);
        /** @var EntityObjectRepositoryLoader&Stub */
        $this->repoLoader = $this->createStub(EntityObjectRepositoryLoader::class);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [MovementRepositoryInterface::class, $this->repoMovement]
            ])
        );
        /** @var Stock&Stub */
        $this->stock = $this->createStub(Stock::class);
        $this->stock->method('sameId')->willReturn(true);
    }

    /** @dataProvider createValues2 */
    public function testIsCreated2(
        string $acquisitionAmountOutstanding,
        string $acquisitionPrice,
        string $acquisitionExpenses,
        string $liquidationAmountRemaining,
        string $liquidationPrice,
        string $liquidationExpenses,
        string $movementAmount,
        string $movementAcquisitionPrice,
        string $movementAcquisitionExpenses,
        string $movementLiquidationPrice,
        string $movementLiquidationExpenses,
    ): void {
        $this->repoMovement->expects($this->once())->method('persist');
        /** @var Currency&MockObject */
        $currency = $this->createStub(Currency::class);
        $currency->method('getDecimals')->willReturn(2);

        /** @var Acquisition&Stub */
        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($this->stock);
        $acquisition->method('getDateTimeUtc')->willReturn(new DateTime('30 minutes ago'));
        $acquisition->method('sameId')->willReturn(true);
        $acquisition->method('getAmountOutstanding')->willReturn(new TransactionAmountOutstandingVO($acquisitionAmountOutstanding));
        $acquisition->method('getPrice')->willReturn(new StockPriceVO($acquisitionPrice, $currency));
        $acquisition->method('getCurrency')->willReturn($currency);
        $acquisition->method('getExpensesUnaccountedFor')->willReturn(new MoneyVO($acquisitionExpenses, $currency));

        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);
        $liquidation->method('getDateTimeUtc')->willReturn(new DateTime('20 minutes ago'));
        $liquidation->method('sameId')->willReturn(true);
        $liquidation->method('getAmountRemaining')->willReturn(new TransactionAmountOutstandingVO($liquidationAmountRemaining));
        $liquidation->method('getPrice')->willReturn(new StockPriceVO($liquidationPrice, $currency));
        $liquidation->method('getCurrency')->willReturn($currency);
        $liquidation->method('getExpensesUnaccountedFor')->willReturn(new MoneyVO($liquidationExpenses, $currency));

        $accountingMovement = new Movement($this->repoLoader, $acquisition, $liquidation);
        $this->assertSame($acquisition, $accountingMovement->getAcquisition());
        $this->assertSame($liquidation, $accountingMovement->getLiquidation());
        $this->assertTrue($accountingMovement->sameId($accountingMovement));
        $this->assertSame((new TransactionAmountVO($movementAmount))->getValue(), $accountingMovement->getAmount()->getValue());
        $this->assertSame($movementAcquisitionPrice, $accountingMovement->getAcquisitionPrice()->getValue());
        $this->assertSame($movementLiquidationPrice, $accountingMovement->getLiquidationPrice()->getValue());
        $this->assertSame($movementAcquisitionExpenses, $accountingMovement->getAcquisitionExpenses()->getValue());
        $this->assertSame($movementLiquidationExpenses, $accountingMovement->getLiquidationExpenses()->getValue());
    }

    public static function createValues2(): array
    {
        return [
            ['10', '57.8000', '23.54', '10', '60.8000', '15.66', '10', '578', '23.54', '608', '15.66'],
            ['100', '578.0000', '23.54', '100', '608.0000', '15.66', '100', '57800', '23.54', '60800', '15.66'],
            ['200', '1.1234', '10.55', '100', '1.5678', '5.45', '100', '112.34', '5.27', '156.78', '5.45'],
            ['95', '61.6634', '10.55', '95', '61.6634', '10.55', '95', '5858.023', '10.55', '5858.023', '10.55'],
            ['10', '5.7800', '4.93', '100', '8.5300', '3.51', '10', '57.8', '4.93', '85.3', '0.35']
        ];
    }

    public function testMovementWithTransactionsHavingDifferentStockThrowsException(): void
    {
        /** @var Stock&Stub */
        $stock = $this->createStub(Stock::class);
        $stock->method('sameId')->willReturn(false);
        /** @var Acquisition&Stub */
        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($stock);
        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($stock);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transactionAssertStock');
        new Movement($this->repoLoader, $acquisition, $liquidation);
    }

    public function testLiquidationDateNotAfterAcquistionThrowsException(): void
    {
        /** @var Acquisition&Stub */
        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($this->stock);
        $acquisition->method('getDateTimeUtc')->willReturn(new DateTime('20 minutes ago'));
        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);     
        $liquidation->method('getDateTimeUtc')->willReturn(new DateTime('30 minutes ago'));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('accountingMovementAssertDateTime');
        new Movement($this->repoLoader, $acquisition, $liquidation);
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        /** @var Movement */
        $accountingMovement = $this->getMockBuilder(Movement::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $entity = new class implements EntityObjectInterface { public function sameId(EntityObjectInterface $otherEntity): bool { return true; }};
        $this->expectException(InvalidArgumentException::class);
        $accountingMovement->sameId($entity);
    }

    public function testAcquisitionWithoutAmountOutstandingThrowsException(): void
    {
        /** @var Acquisition&Stub */
        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($this->stock);
        $acquisition->method('getDateTimeUtc')->willReturn(new DateTime('30 minutes ago'));
        $acquisition->method('getAmountOutstanding')->willReturn(new TransactionAmountOutstandingVO('0'));
        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);     
        $liquidation->method('getDateTimeUtc')->willReturn(new DateTime('20 minutes ago'));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('accountingMovementAmount');
        new Movement($this->repoLoader, $acquisition, $liquidation);
    }
}
