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
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Stock\Transaction\Acquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\Criteria\FiFoCriteria;

/**
 * @covers xVer\MiCartera\Domain\Stock\Transaction\Acquisition
 * @covers xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 */
class AcquistionTest extends TestCase
{
    private Currency $currency;
    private StockPriceVO $price;
    private Stock $stock;
    private static DateTime $dateTimeUtc;
    private static int $amount;
    private MoneyVO $expenses;
    private Account $account;
    private EntityObjectRepositoryLoader $repoLoader;
    /** @var AcquisitionRepositoryInterface&MockObject */
    private AcquisitionRepositoryInterface $repoTransaction;

    public static function setUpBeforeClass(): void
    {
        self::$dateTimeUtc = new DateTime('yesterday', new DateTimeZone('UTC'));
        self::$amount = 100;
    }

    public function setUp(): void
    {
        /** @var Currency&Stub */
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('sameId')->willReturn(true);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->currency->method('getIso3')->willReturn('EUR');
        $this->account = $this->createStub(Account::class);
        $this->price = new StockPriceVO('4.5600', $this->currency);
        $this->expenses = new MoneyVO('23.34', $this->currency);
        /** @var Stock&Stub */
        $this->stock = $this->createStub(Stock::class);        
        $this->stock->method('getCurrency')->willReturn($this->currency);
        $this->stock->method('getPrice')->willReturn($this->price);
        $this->stock->method('sameId')->willReturn(true);
        /** @var AcquisitionRepositoryInterface&MockObject */
        $this->repoTransaction = $this->createMock(AcquisitionRepositoryInterface::class);
        $this->repoTransaction->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        /** @var EntityObjectRepositoryLoader&Stub */
        $this->repoLoader = $this->createStub(EntityObjectRepositoryLoader::class);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [AcquisitionRepositoryInterface::class, $this->repoTransaction]
            ])
        );
    }

    public function testCreate(): void
    {
        /** @var Acquisition&MockObject */
        $transaction = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction->method('fiFoCriteriaInstance')->willReturn($this->createStub(FiFoCriteria::class));
        $this->assertInstanceOf(Acquisition::class, $transaction);
        $this->assertSame($this->stock, $transaction->getStock());
        $this->assertEquals(self::$dateTimeUtc->format('Y-m-d H:i:s'), $transaction->getDateTimeUtc()->format('Y-m-d H:i:s'));
        $this->assertSame(self::$amount, $transaction->getAmount());
        $this->assertEquals($this->price, $transaction->getPrice());
        $this->assertEquals($this->expenses, $transaction->getExpenses());
        $this->assertSame($this->account, $transaction->getAccount());
        $this->assertInstanceOf(Uuid::class, $transaction->getId());
        $this->assertSame($this->currency, $transaction->getCurrency());
        $this->assertTrue($transaction->sameId($transaction));
        $this->assertSame(self::$amount, $transaction->getAmountOutstanding());
        $this->assertEquals($this->expenses, $transaction->getExpensesUnaccountedFor());
    }

    public function testSameIdWithIncorrectEntityArgumentThrowsException(): void
    {
        /** @var Acquisition&MockObject */
        $transaction = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $entity = new class implements EntityObjectInterface { public function sameId(EntityObjectInterface $otherEntity): bool { return true; }};
        $this->expectException(InvalidArgumentException::class);
        $transaction->sameId($entity);
    }

    public function testCreateWithSameAccountStockAndDatetimeWillThrowException(): void
    {
        /** @var AcquisitionRepositoryInterface&Stub */
        $repoTransaction = $this->createStub(AcquisitionRepositoryInterface::class);
        $repoTransaction->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(false);
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [AcquisitionRepositoryInterface::class, $repoTransaction]
            ])
        );
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transExistsOnDateTime');
        /** @var Acquisition&MockObject */
        $transaction = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction->method('fiFoCriteriaInstance')->willReturn($this->createStub(FiFoCriteria::class));
    }

    public function testDateInFutureThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('futureDateNotAllowed');
        $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, new DateTime('tomorrow', new DateTimeZone('UTC')), self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    /**
     * @dataProvider invalidAmount 
     */
    public function testInvalidAmountFormatThrowsException($transAmount): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('numberBetween');
        $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, $transAmount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public static function invalidAmount(): array
    {
        return [
            [1000000],
            [-1]
        ];
    }

    public function testAccountMovementWithWrongAcquisitionThrowsException(): void
    {
        /** @var Acquisition&MockObject */
        $transaction1 = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        /** @var Acquisition&MockObject */
        $transaction2 = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        /** @var Movement&MockObject */
        $movement = $this->createStub(Movement::class);
        $movement->method('getAcquisition')->willReturn($transaction2);
        $this->expectException(InvalidArgumentException::class);
        $transaction1->accountMovement($this->repoLoader, $movement);
    }

    public function testUnaccountMovementWithWrongAcquisitionThrowsException(): void
    {
        /** @var Acquisition&MockObject */
        $transaction1 = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        /** @var Acquisition&MockObject */
        $transaction2 = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        /** @var Movement&MockObject */
        $movement = $this->createStub(Movement::class);
        $movement->method('getAcquisition')->willReturn($transaction2);
        $this->expectException(InvalidArgumentException::class);
        $transaction1->unaccountMovement($this->repoLoader, $movement);
    }

    /**
     * @dataProvider invalidExpenses
     */
    public function testInvalidExpensesValueFormatThrowsException($expense): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('numberBetween');
        /** @var Acquisition&MockObject */
        $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, new MoneyVO($expense, $this->currency), $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public static function invalidExpenses(): array
    {
        return [
            ['100000'],
            ['-1.5']
        ];
    }

    public function testWrongExpensesCurrencyThrowsException(): void
    {
        /** @var MoneyVO&Stub */
        $expenses = $this->createStub(MoneyVO::class);
        $expenses->method('getCurrency')->willReturn($this->createStub(Currency::class));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('otherCurrencyExpected');
        /** @var Acquisition&MockObject */
        $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testAccountMovementCreationAndRemoval(): void
    {
        /** @var Acquisition&MockObject */
        $transaction = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance','sameId'])->getMock();
        $transaction->expects($this->exactly(2))->method('sameId')->willReturn(true);
        /** @var Movement&MockObject */
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->exactly(2))->method('getAmount')->willReturn(self::$amount);
        $movement->expects($this->exactly(2))->method('getAcquisitionExpenses')->willReturn($this->expenses);
        $this->assertSame($transaction, $transaction->accountMovement($this->repoLoader, $movement));
        $this->assertSame(0, $transaction->getAmountOutstanding());
        $this->assertEquals(new MoneyVO('0.00', $this->currency), $transaction->getExpensesUnaccountedFor());
        $transaction->unaccountMovement($this->repoLoader, $movement);
        $this->assertSame(self::$amount, $transaction->getAmountOutstanding());
        $this->assertEquals($this->expenses, $transaction->getExpensesUnaccountedFor());
    }

    public function testMovementWithWrongExpensesAmountThrowsException(): void
    {
        /** @var Acquisition&MockObject */
        $transaction = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance','sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        /** @var Movement&MockObject */
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getAcquisitionExpenses')->willReturn($this->expenses->add(new MoneyVO('1', $this->currency)));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('InvalidMovementExpensesAmount');
        $transaction->accountMovement($this->repoLoader, $movement);
    }

    public function testMovementAmountGreaterThanAmountRemainingThrowsException(): void
    {
        /** @var Acquisition&MockObject */
        $transaction = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance','sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        /** @var Movement&MockObject */
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getAmount')->willReturn(self::$amount+1);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('MovementAmountNotWithinAllowedLimits');
        $transaction->accountMovement($this->repoLoader, $movement);
    }

    public function testCreateIsRolledBackOnTransactionException(): void
    {
        $exception = new Exception();
        $this->repoTransaction->expects($this->once())->method('beginTransaction');
        $this->repoTransaction->expects($this->once())->method('commit')->willThrowException($exception);
        $this->repoTransaction->expects($this->once())->method('rollBack');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        /** @var Acquisition&MockObject */
        $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testPersistRemove(): void
    {
        /** @var Acquisition&MockObject */
        $transaction = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $this->repoTransaction->expects($this->never())->method('beginTransaction');
        $this->repoTransaction->expects($this->once())->method('remove');
        $this->repoTransaction->expects($this->once())->method('flush');
        $this->repoTransaction->expects($this->never())->method('commit');
        $transaction->persistRemove($this->repoLoader);
    }

    public function testPersistRemoveWithAmountRemainingThrowsException(): void
    {
        /** @var Acquisition&MockObject */
        $transaction = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance','sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        /** @var Movement&MockObject */
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getAmount')->willReturn(self::$amount);
        $movement->expects($this->once())->method('getAcquisitionExpenses')->willReturn($this->expenses);
        $this->assertSame($transaction, $transaction->accountMovement($this->repoLoader, $movement));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transBuyCannotBeRemovedWithoutFullAmountOutstanding');
        $transaction->persistRemove($this->repoLoader);
    }

    public function testExceptionIsThrownOnCreateCommitFail(): void
    {
        $this->repoTransaction->expects($this->once())->method('commit')->willThrowException(new Exception());
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        /** @var Acquisition&MockObject */
        $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testExceptionIsThrownOnRemoveCommitFail(): void
    {
        /** @var Acquisition&MockObject */
        $transaction = $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
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
        /** @var Acquisition&MockObject */
        $this->getMockBuilder(Acquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }
}
