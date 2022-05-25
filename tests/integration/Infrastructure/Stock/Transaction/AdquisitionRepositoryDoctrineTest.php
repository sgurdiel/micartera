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
use xVer\MiCartera\Domain\Portfolio\SummaryVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\Adquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\Transaction\AdquisitionRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\Stock\Transaction\AdquisitionRepositoryDoctrine
 * @covers xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @covers xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Accounting\Movement
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Portfolio\SummaryVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Adquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Criteria\FifoCriteria
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Liquidation
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Accounting\MovementRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine
 */
class AdquisitionRepositoryDoctrineTest extends IntegrationTestCase
{
    private AdquisitionRepositoryInterface $repo;
    private Account $account;
    private Account $account2;
    private Stock $stock;
    private Stock $stock2;
    private Stock $stock3;
    private MoneyVO $expenses;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->repo = new AdquisitionRepositoryDoctrine(self::$registry);
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
        $amount = 399;
        $transaction = new Adquisition(
            $this->repoLoader,
            $this->stock, new DateTime('yesterday', new DateTimeZone('UTC')), $amount, $this->expenses, $this->account);
        $this->assertInstanceOf(Adquisition::class, $transaction);
        $this->assertEquals($this->stock->getPrice(), $transaction->getPrice());
        $this->assertSame($amount, $transaction->getAmount());
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $transaction = $this->repo->findByIdOrThrowException($transactionId);
        $this->assertInstanceOf(Adquisition::class, $transaction);
        $this->assertEquals($transactionId, $transaction->getId());
        $transaction->persistRemove($this->repoLoader);
        parent::detachEntity($transaction);
        $this->assertSame(null, $this->repo->findById($transactionId));
    }

    public function testfindById(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Adquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('yesterday', new DateTimeZone('UTC')), 654, $this->expenses, $this->account
        );
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $this->assertInstanceOf(Adquisition::class, $this->repo->findById($transactionId));
        $this->assertSame($transactionId, $transaction->getId());
    }

    public function testfindByIdWithNonExistingReturnsNull(): void
    {
        $this->assertNull($this->repo->findById(Uuid::v4()));
    }

    public function testfindByIdOrThrowException(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Adquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('30 minutes ago', new DateTimeZone('UTC')), 654, $this->expenses, $this->account
        );
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $this->assertInstanceOf(Adquisition::class, $this->repo->findByIdOrThrowException($transactionId));
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
        $this->assertInstanceOf(AdquisitionsCollection::class, $transactionsCollection);
        $this->assertSame(0, $transactionsCollection->count());
        new Adquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('2 hours ago', new DateTimeZone('UTC')), 654, $this->expenses, $this->account
        );
        new Adquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('1 hour ago', new DateTimeZone('UTC')), 654, $this->expenses, $this->account
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
        $this->assertInstanceOf(AdquisitionsCollection::class, $transactionsCollection);
        $this->assertSame(0, $transactionsCollection->count());
    }

    public function testMultipleWithSameAccountStockAndDateTimeThrowsException(): void
    {
        parent::$loadFixtures = true;
        $date = new DateTime('5 hours ago', new DateTimeZone('UTC'));
        new Adquisition(
            $this->repoLoader,
            $this->stock, $date, 544, $this->expenses, $this->account
        );
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transExistsOnDateTime');
        new Adquisition(
            $this->repoLoader,
            $this->stock, $date, 544, $this->expenses, $this->account
        );
    }

    public function testRemovalWhenNotFullAmountOutstandingThrowsException(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Adquisition(
            $this->repoLoader,
            $this->stock3, new DateTime('90 minutes ago', new DateTimeZone('UTC')), 1500, $this->expenses, $this->account);
        new Liquidation(       
            $this->repoLoader,
            $this->stock3, new DateTime('89 minutes ago', new DateTimeZone('UTC')), 420, $this->expenses, $this->account);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transBuyCannotBeRemovedWithoutFullAmountOutstanding');
        $transaction->persistRemove($this->repoLoader);
    }

    public function testFindByAccountWithAmountOutstanding(): void
    {
        parent::$loadFixtures = true;
        $transaction1 = new Adquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('2021-09-21 09:44:12', new DateTimeZone('UTC')), 440, $this->expenses, $this->account2);
        $transaction2 = new Adquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('2021-09-23 10:51:21s', new DateTimeZone('UTC')), 600, $this->expenses, $this->account2);
        $transactionsCollection = $this->repo->findByAccountWithAmountOutstanding($this->account2, 'ASC', 'datetimeutc', 0, 0);
        $this->assertSame(2, $transactionsCollection->count());
        $this->assertInstanceOf(Adquisition::class, $transactionsCollection->offsetGet(0));
        $this->assertInstanceOf(Adquisition::class, $transactionsCollection->offsetGet(1));
        $this->assertEquals($transaction1->getId(), $transactionsCollection->offsetGet(0)->getId());
        $this->assertEquals($transaction2->getId(), $transactionsCollection->offsetGet(1)->getId());

        $transactionsCollection = $this->repo->findByAccountWithAmountOutstanding($this->account2, 'DESC', 'datetimeutc', 0, 0);
        $this->assertSame(2, $transactionsCollection->count());
        $this->assertInstanceOf(Adquisition::class, $transactionsCollection->offsetGet(0));
        $this->assertInstanceOf(Adquisition::class, $transactionsCollection->offsetGet(1));
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
}
