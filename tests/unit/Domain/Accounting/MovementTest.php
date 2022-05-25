<?php declare(strict_types=1);

namespace Tests\unit\Domain\Accounting;

use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Domain\Accounting\Movement;
use xVer\MiCartera\Domain\Accounting\MovementRepositoryInterface;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Stock\Transaction\Adquisition;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;

/**
 * @covers xVer\MiCartera\Domain\Accounting\Movement
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 */
class MovementTest extends TestCase
{
    private EntityObjectRepositoryLoader $repoLoader;
    /** @var Stock&Stub */
    private Stock $stock;
    /** @var MovementRepositoryInterface&MockObject */
    private MovementRepositoryInterface $repoMovement;

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

    /** @dataProvider createValues */
    public function testIsCreated(
        int $liquidationAmountRemaining, int $movementAmount, string $adquisitionPrice, string $liquidationPrice, string $adquisitionExpenses, string $liquidationExpenses
    ): void {
        $this->repoMovement->expects($this->once())->method('persist');
        /** @var Currency&MockObject */
        $currency = $this->createStub(Currency::class);
        $currency->method('getDecimals')->willReturn(2);

        /** @var Adquisition&Stub */
        $adquisition = $this->createStub(Adquisition::class);
        $adquisition->method('getStock')->willReturn($this->stock);
        $adquisition->method('getDateTimeUtc')->willReturn(new DateTime('30 minutes ago'));
        $adquisition->method('sameId')->willReturn(true);
        $adquisition->method('getAmountOutstanding')->willReturn(100);
        $adquisition->method('getPrice')->willReturn(new StockPriceVO('5.78', $currency));
        $adquisition->method('getCurrency')->willReturn($currency);
        $adquisition->method('getExpensesUnaccountedFor')->willReturn(new MoneyVO('4.93', $currency));

        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);
        $liquidation->method('getDateTimeUtc')->willReturn(new DateTime('20 minutes ago'));
        $liquidation->method('sameId')->willReturn(true);
        $liquidation->method('getAmountRemaining')->willReturn($liquidationAmountRemaining);
        $liquidation->method('getPrice')->willReturn(new StockPriceVO('8.53', $currency));
        $liquidation->method('getCurrency')->willReturn($currency);
        $liquidation->method('getExpensesUnaccountedFor')->willReturn(new MoneyVO('3.51', $currency));

        $accountingMovement = new Movement($this->repoLoader, $adquisition, $liquidation);
        $this->assertSame($adquisition, $accountingMovement->getAdquisition());
        $this->assertSame($liquidation, $accountingMovement->getLiquidation());
        $this->assertTrue($accountingMovement->sameId($accountingMovement));
        $this->assertSame($movementAmount, $accountingMovement->getAmount());
        $this->assertSame($adquisitionPrice, $accountingMovement->getAdquisitionPrice()->getValue());
        $this->assertSame($liquidationPrice, $accountingMovement->getLiquidationPrice()->getValue());
        $this->assertSame($adquisitionExpenses, $accountingMovement->getAdquisitionExpenses()->getValue());
        $this->assertSame($liquidationExpenses, $accountingMovement->getLiquidationExpenses()->getValue());
    }

    public static function createValues(): array
    {
        return [
            [10, 10, '57.80', '85.30', '0.49', '3.51'],
            [100, 100, '578.00', '853.00', '4.93', '3.51'],
            [200, 100, '578.00', '853.00', '4.93', '1.75']
        ];
    }

    public function testMovementWithTransactionsHavingDifferentStockThrowsException(): void
    {
        /** @var Stock&Stub */
        $stock = $this->createStub(Stock::class);
        $stock->method('sameId')->willReturn(false);
        /** @var Adquisition&Stub */
        $adquisition = $this->createStub(Adquisition::class);
        $adquisition->method('getStock')->willReturn($stock);
        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($stock);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transactionAssertStock');
        new Movement($this->repoLoader, $adquisition, $liquidation);
    }

    public function testLiquidationDateNotAfterAdquistionThrowsException(): void
    {
        /** @var Adquisition&Stub */
        $adquisition = $this->createStub(Adquisition::class);
        $adquisition->method('getStock')->willReturn($this->stock);
        $adquisition->method('getDateTimeUtc')->willReturn(new DateTime('20 minutes ago'));
        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);     
        $liquidation->method('getDateTimeUtc')->willReturn(new DateTime('30 minutes ago'));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('accountingMovementAssertDateTime');
        new Movement($this->repoLoader, $adquisition, $liquidation);
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        /** @var Movement */
        $accountingMovement = $this->getMockBuilder(Movement::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $entity = new class implements EntityObjectInterface { public function sameId(EntityObjectInterface $otherEntity): bool { return true; }};
        $this->expectException(InvalidArgumentException::class);
        $accountingMovement->sameId($entity);
    }

    public function testAdquisitionWithoutAmountOutstandingThrowsException(): void
    {
        /** @var Adquisition&Stub */
        $adquisition = $this->createStub(Adquisition::class);
        $adquisition->method('getStock')->willReturn($this->stock);
        $adquisition->method('getDateTimeUtc')->willReturn(new DateTime('30 minutes ago'));
        $adquisition->method('getAmountOutstanding')->willReturn(0);
        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);     
        $liquidation->method('getDateTimeUtc')->willReturn(new DateTime('20 minutes ago'));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('accountingMovementAmount');
        new Movement($this->repoLoader, $adquisition, $liquidation);
    }
}
