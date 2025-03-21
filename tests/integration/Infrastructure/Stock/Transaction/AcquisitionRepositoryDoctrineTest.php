<?php declare(strict_types=1);

namespace Tests\integration\Infrastructure\Stock\Transaction;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Uid\Uuid;
use Tests\integration\IntegrationTestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Portfolio\SummaryVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO;
use xVer\MiCartera\Domain\Stock\Transaction\Acquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\Transaction\AcquisitionRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\Stock\Transaction\AcquisitionRepositoryDoctrine
 * @covers xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @covers xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Exchange\Exchange
 * @uses xVer\MiCartera\Domain\Stock\Accounting\Movement
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Portfolio\SummaryVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Acquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Criteria\FifoCriteria
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Liquidation
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountOutstandingVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Accounting\MovementRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Exchange\ExchangeRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine
 */
class AcquisitionRepositoryDoctrineTest extends IntegrationTestCase
{
    private AcquisitionRepositoryInterface $repo;
    private Account $account;
    private Account $account2;
    private Stock $stock;
    private Stock $stock2;
    private Stock $stock3;
    private MoneyVO $expenses;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->repo = new AcquisitionRepositoryDoctrine(self::$registry);
        $repoAccount = new AccountRepositoryDoctrine(self::$registry);
        $repoStock = new StockRepositoryDoctrine(self::$registry);
        $this->account = $repoAccount->findByIdentifier('test@example.com');
        $this->account2 =$repoAccount->findByIdentifier('test_other@example.com');
        $this->stock = $repoStock->findById('CABK');
        $this->stock2 = $repoStock->findById('SAN');
        $this->stock3 = $repoStock->findById('ROVI');
        $this->expenses = new MoneyVO('11.43', $this->account->getCurrency());
    }

    public function testIsCreatedAndRemoved(): void
    {
        $amount = new TransactionAmountVO('399');
        $transaction = new Acquisition(
            $this->repoLoader,
            $this->stock, new DateTime('yesterday', new DateTimeZone('UTC')), $amount, $this->expenses, $this->account);
        $this->assertInstanceOf(Acquisition::class, $transaction);
        $this->assertEquals($this->stock->getPrice(), $transaction->getPrice());
        $this->assertSame($amount->getValue(), $transaction->getAmount()->getValue());
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $transaction = $this->repo->findByIdOrThrowException($transactionId);
        $this->assertInstanceOf(Acquisition::class, $transaction);
        $this->assertEquals($transactionId, $transaction->getId());
        $transaction->persistRemove($this->repoLoader);
        parent::detachEntity($transaction);
        $this->assertSame(null, $this->repo->findById($transactionId));
    }

    public function testfindById(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Acquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('yesterday', new DateTimeZone('UTC')), new TransactionAmountVO('654'), $this->expenses, $this->account
        );
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $this->assertInstanceOf(Acquisition::class, $this->repo->findById($transactionId));
        $this->assertSame($transactionId, $transaction->getId());
    }

    public function testfindByIdWithNonExistingReturnsNull(): void
    {
        $this->assertNull($this->repo->findById(Uuid::v4()));
    }

    public function testfindByIdOrThrowException(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Acquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('30 minutes ago', new DateTimeZone('UTC')), new TransactionAmountVO('654'), $this->expenses, $this->account
        );
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $this->assertInstanceOf(Acquisition::class, $this->repo->findByIdOrThrowException($transactionId));
    }

    public function testfindByIdOrThrowExceptionWithNonExistingThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('expectedPersistedObjectNotFound');
        $this->repo->findByIdOrThrowException(Uuid::v4());
    }

    public function testFindByStockId(): void
    {
        parent::$loadFixtures = true;
        $transactionsCollection = $this->repo->findByStockId($this->stock2, 20, 0);
        $this->assertInstanceOf(AcquisitionsCollection::class, $transactionsCollection);
        $this->assertSame(0, $transactionsCollection->count());
        new Acquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('2 hours ago', new DateTimeZone('UTC')), new TransactionAmountVO('654'), $this->expenses, $this->account
        );
        new Acquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('1 hour ago', new DateTimeZone('UTC')), new TransactionAmountVO('654'), $this->expenses, $this->account
        );
        $transactionsCollection = $this->repo->findByStockId($this->stock2, 20, 0);
        $this->assertSame(2, $transactionsCollection->count());
        foreach ($transactionsCollection->toArray() as $transaction) {
            $this->assertSame($this->stock2->getId(), $transaction->getStock()->getId());
        }
        $transactionsCollection = $this->repo->findByStockId($this->stock2, 1, 0);
        $this->assertSame(1, $transactionsCollection->count());
    }

    public function testFindByStockIdWithNonExistentStockReturnsEmptyCollection(): void
    {
        /** @var Stock&Stub */
        $stock = $this->createStub(Stock::class);
        $stock->method('getId')->willReturn('NONEXISTENT');
        $transactionsCollection = $this->repo->findByStockId($stock, 2, 0);
        $this->assertInstanceOf(AcquisitionsCollection::class, $transactionsCollection);
        $this->assertSame(0, $transactionsCollection->count());
    }

    public function testMultipleWithSameAccountStockAndDateTimeThrowsException(): void
    {
        parent::$loadFixtures = true;
        $date = new DateTime('5 hours ago', new DateTimeZone('UTC'));
        new Acquisition(
            $this->repoLoader,
            $this->stock, $date, new TransactionAmountVO('544'), $this->expenses, $this->account
        );
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transExistsOnDateTime');
        new Acquisition(
            $this->repoLoader,
            $this->stock, $date, new TransactionAmountVO('544'), $this->expenses, $this->account
        );
    }

    public function testRemovalWhenNotFullAmountOutstandingThrowsException(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Acquisition(
            $this->repoLoader,
            $this->stock3, new DateTime('90 minutes ago', new DateTimeZone('UTC')), new TransactionAmountVO('1500'), $this->expenses, $this->account);
        new Liquidation(       
            $this->repoLoader,
            $this->stock3, new DateTime('89 minutes ago', new DateTimeZone('UTC')), new TransactionAmountVO('420'), $this->expenses, $this->account);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transBuyCannotBeRemovedWithoutFullAmountOutstanding');
        $transaction->persistRemove($this->repoLoader);
    }

    public function testFindByAccountWithAmountOutstanding(): void
    {
        parent::$loadFixtures = true;
        $transaction1 = new Acquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('2021-09-21 09:44:12', new DateTimeZone('UTC')), new TransactionAmountVO('440'), $this->expenses, $this->account2);
        $transaction2 = new Acquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('2021-09-23 10:51:21s', new DateTimeZone('UTC')), new TransactionAmountVO('600'), $this->expenses, $this->account2);
        $transactionsCollection = $this->repo->findByAccountWithAmountOutstanding($this->account2, 'ASC', 'datetimeutc', 0, 0);
        $this->assertSame(2, $transactionsCollection->count());
        $this->assertInstanceOf(Acquisition::class, $transactionsCollection->offsetGet(0));
        $this->assertInstanceOf(Acquisition::class, $transactionsCollection->offsetGet(1));
        $this->assertEquals($transaction1->getId(), $transactionsCollection->offsetGet(0)->getId());
        $this->assertEquals($transaction2->getId(), $transactionsCollection->offsetGet(1)->getId());

        $transactionsCollection = $this->repo->findByAccountWithAmountOutstanding($this->account2, 'DESC', 'datetimeutc', 0, 0);
        $this->assertSame(2, $transactionsCollection->count());
        $this->assertInstanceOf(Acquisition::class, $transactionsCollection->offsetGet(0));
        $this->assertInstanceOf(Acquisition::class, $transactionsCollection->offsetGet(1));
        $this->assertEquals($transaction2->getId(), $transactionsCollection->offsetGet(0)->getId());
        $this->assertEquals($transaction1->getId(), $transactionsCollection->offsetGet(1)->getId());

        $transactionsCollection = $this->repo->findByAccountWithAmountOutstanding($this->account2, 'ASC', 'datetimeutc', 1, 0);
        $this->assertSame(1, $transactionsCollection->count());
    }

    public function testPortfolioSummary(): void
    {
        $summary = $this->repo->portfolioSummary($this->account);
        $this->assertInstanceOf(SummaryVO::class, $summary);
    }

    public function testStockPortfolioSummary(): void
    {
        $summary = $this->repo->portfolioSummary($this->account, $this->stock);
        $this->assertInstanceOf(SummaryVO::class, $summary);
    }
}
