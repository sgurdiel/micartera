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
use xVer\MiCartera\Domain\Accounting\Movement;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Stock\Transaction\Adquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\Criteria\FiFoCriteria;

/**
 * @covers xVer\MiCartera\Domain\Stock\Transaction\Adquisition
 * @covers xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 */
class AdquistionTest extends TestCase
{
    private Currency $currency;
    private StockPriceVO $price;
    private Stock $stock;
    private static DateTime $dateTimeUtc;
    private static int $amount;
    private MoneyVO $expenses;
    private Account $account;
    private EntityObjectRepositoryLoader $repoLoader;
    /** @var AdquisitionRepositoryInterface&MockObject */
    private AdquisitionRepositoryInterface $repoTransaction;

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
        /** @var AdquisitionRepositoryInterface&MockObject */
        $this->repoTransaction = $this->createMock(AdquisitionRepositoryInterface::class);
        $this->repoTransaction->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        /** @var EntityObjectRepositoryLoader&Stub */
        $this->repoLoader = $this->createStub(EntityObjectRepositoryLoader::class);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [AdquisitionRepositoryInterface::class, $this->repoTransaction]
            ])
        );
    }

    public function testCreate(): void
    {
        /** @var Adquisition&MockObject */
        $transaction = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction->method('fiFoCriteriaInstance')->willReturn($this->createStub(FiFoCriteria::class));
        $this->assertInstanceOf(Adquisition::class, $transaction);
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
        /** @var Adquisition&MockObject */
        $transaction = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $entity = new class implements EntityObjectInterface { public function sameId(EntityObjectInterface $otherEntity): bool { return true; }};
        $this->expectException(InvalidArgumentException::class);
        $transaction->sameId($entity);
    }

    public function testCreateWithSameAccountStockAndDatetimeWillThrowException(): void
    {
        /** @var AdquisitionRepositoryInterface&Stub */
        $repoTransaction = $this->createStub(AdquisitionRepositoryInterface::class);
        $repoTransaction->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(false);
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [AdquisitionRepositoryInterface::class, $repoTransaction]
            ])
        );
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transExistsOnDateTime');
        /** @var Adquisition&MockObject */
        $transaction = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction->method('fiFoCriteriaInstance')->willReturn($this->createStub(FiFoCriteria::class));
    }

    public function testDateInFutureThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('futureDateNotAllowed');
        $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
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
        $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
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

    public function testAccountMovementWithWrongAdquisitionThrowsException(): void
    {
        /** @var Adquisition&MockObject */
        $transaction1 = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        /** @var Adquisition&MockObject */
        $transaction2 = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        /** @var Movement&MockObject */
        $movement = $this->createStub(Movement::class);
        $movement->method('getAdquisition')->willReturn($transaction2);
        $this->expectException(InvalidArgumentException::class);
        $transaction1->accountMovement($this->repoLoader, $movement);
    }

    public function testUnaccountMovementWithWrongAdquisitionThrowsException(): void
    {
        /** @var Adquisition&MockObject */
        $transaction1 = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        /** @var Adquisition&MockObject */
        $transaction2 = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        /** @var Movement&MockObject */
        $movement = $this->createStub(Movement::class);
        $movement->method('getAdquisition')->willReturn($transaction2);
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
        /** @var Adquisition&MockObject */
        $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
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
        /** @var Adquisition&MockObject */
        $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testAccountMovementCreationAndRemoval(): void
    {
        /** @var Adquisition&MockObject */
        $transaction = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance','sameId'])->getMock();
        $transaction->expects($this->exactly(2))->method('sameId')->willReturn(true);
        /** @var Movement&MockObject */
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->exactly(2))->method('getAmount')->willReturn(self::$amount);
        $movement->expects($this->exactly(2))->method('getAdquisitionExpenses')->willReturn($this->expenses);
        $this->assertSame($transaction, $transaction->accountMovement($this->repoLoader, $movement));
        $this->assertSame(0, $transaction->getAmountOutstanding());
        $this->assertEquals(new MoneyVO('0.00', $this->currency), $transaction->getExpensesUnaccountedFor());
        $transaction->unaccountMovement($this->repoLoader, $movement);
        $this->assertSame(self::$amount, $transaction->getAmountOutstanding());
        $this->assertEquals($this->expenses, $transaction->getExpensesUnaccountedFor());
    }

    public function testMovementWithWrongExpensesAmountThrowsException(): void
    {
        /** @var Adquisition&MockObject */
        $transaction = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance','sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        /** @var Movement&MockObject */
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getAdquisitionExpenses')->willReturn($this->expenses->add(new MoneyVO('1', $this->currency)));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('InvalidMovementExpensesAmount');
        $transaction->accountMovement($this->repoLoader, $movement);
    }

    public function testMovementAmountGreaterThanAmountRemainingThrowsException(): void
    {
        /** @var Adquisition&MockObject */
        $transaction = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
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
        /** @var Adquisition&MockObject */
        $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testPersistRemove(): void
    {
        /** @var Adquisition&MockObject */
        $transaction = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
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
        /** @var Adquisition&MockObject */
        $transaction = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance','sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        /** @var Movement&MockObject */
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getAmount')->willReturn(self::$amount);
        $movement->expects($this->once())->method('getAdquisitionExpenses')->willReturn($this->expenses);
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
        /** @var Adquisition&MockObject */
        $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testExceptionIsThrownOnRemoveCommitFail(): void
    {
        /** @var Adquisition&MockObject */
        $transaction = $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $this->repoTransaction->expects($this->once())->method('remove')->willThrowException(new Exception());
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        $transaction->persistRemove($this->repoLoader);
    }

    public function testDomainExceptionWhileInCreateTransactionThrowsDomainException(): void
    {
        $this->repoTransaction->expects($this->once())->method('persist')->willThrowException($this->createStub(DomainException::class));
        $this->expectException(DomainException::class);
        /** @var Adquisition&MockObject */
        $this->getMockBuilder(Adquisition::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->repoLoader, $this->stock, self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }
}
