<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock\Transaction;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Accounting\Movement;
use xVer\MiCartera\Domain\Stock\Accounting\MovementRepositoryInterface;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Number\Number;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationRepositoryInterface;

/**
 * @covers xVer\MiCartera\Domain\Stock\Transaction\Liquidation
 * @covers xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Accounting\Movement
 * @uses xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountOutstandingVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO
 */

class LiquidationTest extends TestCase
{
    private Currency&Stub $currency;
    private StockPriceVO $price;
    private Stock&Stub $stock;
    private static DateTime $dateTimeUtc;
    private static TransactionAmountVO $amount;
    private MoneyVO $expenses;
    private Account&Stub $account;
    private EntityObjectRepositoryLoader&Stub $repoLoader;
    private LiquidationRepositoryInterface&MockObject $repoTransaction;   

    public static function setUpBeforeClass(): void
    {
        self::$dateTimeUtc = new DateTime('yesterday', new DateTimeZone('UTC'));
        self::$amount = new TransactionAmountVO('100');
    }

    public function setUp(): void
    {
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('sameId')->willReturn(true);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->currency->method('getIso3')->willReturn('EUR');
        $this->account = $this->createStub(Account::class);
        $this->price = new StockPriceVO('4.5600', $this->currency);
        $this->expenses = new MoneyVO('23.34', $this->currency);
        $this->stock = $this->createStub(Stock::class);        
        $this->stock->method('getCurrency')->willReturn($this->currency);
        $this->stock->method('getPrice')->willReturn($this->price);
        $this->stock->method('sameId')->willReturn(true);
        $this->repoTransaction = $this->createMock(LiquidationRepositoryInterface::class);
        $this->repoTransaction->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        /** @var EntityObjectRepositoryLoader&Stub */
        $this->repoLoader = $this->createStub(EntityObjectRepositoryLoader::class);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [LiquidationRepositoryInterface::class, $this->repoTransaction]
            ])
        );
    }

    public function testCreate(): void
    {
        $this->repoTransaction->expects($this->once())->method('beginTransaction');
        $this->repoTransaction->expects($this->once())->method('persist');
        $this->repoTransaction->expects($this->once())->method('flush');
        $this->repoTransaction->expects($this->once())->method('commit');
        /** @var Liquidation&MockObject */
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $this->assertInstanceOf(Liquidation::class, $transaction);
        $this->assertSame($this->stock, $transaction->getStock());
        $this->assertEquals(self::$dateTimeUtc->format('Y-m-d H:i:s'), $transaction->getDateTimeUtc()->format('Y-m-d H:i:s'));
        $this->assertSame(self::$amount->getValue(), $transaction->getAmount()->getValue());
        $this->assertEquals($this->price, $transaction->getPrice());
        $this->assertEquals($this->expenses, $transaction->getExpenses());
        $this->assertSame($this->account, $transaction->getAccount());
        $this->assertInstanceOf(Uuid::class, $transaction->getId());
        $this->assertSame($this->currency, $transaction->getCurrency());
        $this->assertTrue($transaction->sameId($transaction));
        $this->assertSame(self::$amount->getValue(), $transaction->getAmountRemaining()->getValue());
        $this->assertEquals($this->expenses, $transaction->getExpensesUnaccountedFor());
    }

    public function testCreateWithSameAccountStockAndDatetimeWillThrowException(): void
    {
        /** @var LiquidationRepositoryInterface&Stub */
        $repoTransaction = $this->createStub(LiquidationRepositoryInterface::class);
        $repoTransaction->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(false);
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [LiquidationRepositoryInterface::class, $repoTransaction]
            ])
        );
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transExistsOnDateTime');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testDateInFutureThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('futureDateNotAllowed');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, new DateTime('tomorrow', new DateTimeZone('UTC')), self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    /**
     * @dataProvider invalidAmount 
     */
    public function testInvalidAmountFormatThrowsException($transAmount): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('enterNumberBetween');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, new TransactionAmountVO($transAmount), $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public static function invalidAmount(): array
    {
        return [
            ['1000000000'],
            ['-1'],
            ['0']
        ];
    }

    public function testMovementWithWrongLiquidationThrowsException(): void
    {
        /** @var Liquidation&MockObject */
        $transaction1 = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        /** @var Liquidation&MockObject */
        $transaction2 = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        /** @var Movement&MockObject */
        $movement = $this->createStub(Movement::class);
        $movement->method('getLiquidation')->willReturn($transaction2);
        $this->expectException(InvalidArgumentException::class);
        $transaction1->accountMovement($this->repoLoader, $movement);
    }
    
    /**
     * @dataProvider invalidExpenses
     */
    public function testInvalidExpensesValueFormatThrowsException($expense): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('enterNumberBetween');
        /** @var Liquidation&MockObject */
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, new MoneyVO($expense, $this->currency), $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public static function invalidExpenses(): array
    {
        return [
            ['1000000000000'],
            ['-1.5']
        ];
    }

    public function testSameIdWithIncorrectEntityArgumentThrowsException(): void
    {
        /** @var Liquidation&MockObject */
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $entity = new class implements EntityObjectInterface { public function sameId(EntityObjectInterface $otherEntity): bool { return true; }};
        $this->expectException(InvalidArgumentException::class);
        $transaction->sameId($entity);
    }

    public function testWrongExpensesCurrencyThrowsException(): void
    {
        /** @var MoneyVO&Stub */
        $expenses = $this->createStub(MoneyVO::class);
        $expenses->method('getCurrency')->willReturn($this->createStub(Currency::class));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('otherCurrencyExpected');
        /** @var Liquidation&MockObject */
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testAccountMovementAndClearMovements(): void
    {
        /** @var MovementRepositoryInterface&MockObject */
        $movementRepo = $this->createMock(MovementRepositoryInterface::class);
        $movementRepo->expects($this->once())->method('remove');
        $movementRepo->expects($this->once())->method('flush');
        /** @var EntityObjectRepositoryLoader&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoader::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [LiquidationRepositoryInterface::class, $this->repoTransaction],
                [MovementRepositoryInterface::class, $movementRepo]
            ])
        );
        /** @var Liquidation&MockObject */
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance','sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        /** @var Movement&MockObject */
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getAmount')->willReturn(self::$amount);
        $movement->expects($this->exactly(2))->method('getLiquidationExpenses')->willReturn($this->expenses);
        $this->assertSame($transaction, $transaction->accountMovement($repoLoader, $movement));
        $this->assertSame('0', $transaction->getAmountRemaining()->getValue());
        $this->assertEquals(new MoneyVO('0.00', $this->currency), $transaction->getExpensesUnaccountedFor());
        $acquisitionsCollection = $transaction->clearMovementsCollection($repoLoader);
        $this->assertInstanceOf(AcquisitionsCollection::class, $acquisitionsCollection);
        $this->assertSame(self::$amount->getValue(), $transaction->getAmountRemaining()->getValue());
        $this->assertEquals($this->expenses, $transaction->getExpensesUnaccountedFor());  
    }

    public function testMovementWithWrongExpensesAmountThrowsException(): void
    {
        /** @var Liquidation&MockObject */
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance','sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        /** @var Movement&MockObject */
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getLiquidationExpenses')->willReturn($this->expenses->add(new MoneyVO('1', $this->currency)));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('InvalidMovementExpensesAmount');
        $transaction->accountMovement($this->repoLoader, $movement);
    }

    public function testMovementAmountGreaterThanAmountRemainingThrowsException(): void
    {
        /** @var Liquidation&MockObject */
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance','sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        /** @var Movement&MockObject */
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getAmount')->willReturn(
            new TransactionAmountVO(bcadd(self::$amount->getValue(),'1'))
        );
        $movement->expects($this->once())->method('getLiquidationExpenses')->willReturn(new MoneyVO('4.56', $this->currency));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('MovementAmountNotWithinAllowedLimits');
        $transaction->accountMovement($this->repoLoader, $movement);
    }

    public function testCreateIsRolledBackOnTransactionException(): void
    {
        $this->repoTransaction->expects($this->once())->method('beginTransaction');
        $this->repoTransaction->expects($this->once())->method('commit')->willThrowException(new Exception());
        $this->repoTransaction->expects($this->once())->method('rollBack');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        /** @var Liquidation&MockObject */
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testPersistRemove(): void
    {
        /** @var Liquidation&MockObject */
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $this->repoTransaction->expects($this->once())->method('beginTransaction');
        $this->repoTransaction->expects($this->once())->method('remove');
        $this->repoTransaction->expects($this->once())->method('flush');
        $this->repoTransaction->expects($this->once())->method('commit');
        $transaction->persistRemove($this->repoLoader);
    }

    public function testRemoveIsRolledBackOnTransactionException(): void
    {
        $exception = new Exception();
        /** @var Liquidation&MockObject */
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $this->repoTransaction->expects($this->once())->method('beginTransaction');
        $this->repoTransaction->expects($this->once())->method('remove')->willThrowException($exception);
        $this->repoTransaction->expects($this->once())->method('rollBack');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        $transaction->persistRemove($this->repoLoader);
    }

    public function testExceptionIsThrownOnCreateCommitFail(): void
    {
        $this->repoTransaction->expects($this->once())->method('commit')->willThrowException(new Exception());
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        /** @var Liquidation&MockObject */
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testExceptionIsThrownOnRemoveCommitFail(): void
    {
        /** @var Liquidation&MockObject */
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $this->repoTransaction->expects($this->once())->method('remove')->willThrowException(new Exception());
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        $transaction->persistRemove($this->repoLoader);
    }

    public function testDomainExceptionWhileInCreateTransactionThrowsDomainException(): void
    {
        /** @var DomainException */
        $domainException = $this->createStub(DomainException::class);
        $this->repoTransaction->expects($this->once())->method('persist')->willThrowException($domainException);
        $this->expectException(DomainException::class);
        /** @var Liquidation&MockObject */
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testDomainExceptionWhileInRemoveTransactionThrowsDomainException(): void
    {
        /** @var Liquidation&MockObject */
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        /** @var DomainException */
        $domainException = $this->createStub(DomainException::class);
        $transaction->expects($this->once())->method('fiFoCriteriaInstance')->willThrowException($domainException);
        $this->expectException(DomainException::class);
        $transaction->persistRemove($this->repoLoader);
    }
}
