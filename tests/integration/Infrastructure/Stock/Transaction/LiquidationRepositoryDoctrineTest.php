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
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO;
use xVer\MiCartera\Domain\Stock\Transaction\Acquisition;
use xVer\MiCartera\Domain\Stock\Transaction\Criteria\FiFoCriteria;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationsCollection;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine
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
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Acquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Criteria\FifoCriteria
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Liquidation
 * @uses xVer\MiCartera\Domain\Stock\Transaction\LiquidationsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountOutstandingVO
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Accounting\MovementRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Exchange\ExchangeRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\AcquisitionRepositoryDoctrine
 */
class LiquidationRepositoryDoctrineTest extends IntegrationTestCase
{
    private LiquidationRepositoryInterface $repo;
    private Account $account;
    private Stock $stock;
    private Stock $stock2;
    private MoneyVO $expenses;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->repo = new LiquidationRepositoryDoctrine(self::$registry);
        $repoAccount = new AccountRepositoryDoctrine(self::$registry);
        $repoStock = new StockRepositoryDoctrine(self::$registry);
        $this->account = $repoAccount->findByIdentifier('test@example.com');
        $this->stock = $repoStock->findById('CABK');
        $this->stock2 = $repoStock->findById('SAN');
        $this->expenses = new MoneyVO('11.43', $this->account->getCurrency());
    }

    public function testIsCreatedAndRemoved(): void
    {
        $transaction = new Liquidation(
            $this->repoLoader,
            $this->stock, new DateTime('yesterday', new DateTimeZone('UTC')), new TransactionAmountVO('99'), $this->expenses, $this->account
        );
        $this->assertInstanceOf(Liquidation::class, $transaction);
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $transaction = $this->repo->findByIdOrThrowException($transactionId);
        $this->assertInstanceOf(Liquidation::class, $transaction);
        $transaction->persistRemove($this->repoLoader, new FiFoCriteria($this->repoLoader));
        parent::detachEntity($transaction);
        $this->assertSame(null, $this->repo->findById($transactionId));
    }

    public function testfindById(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Liquidation(
            $this->repoLoader,
            $this->stock, new DateTime('yesterday', new DateTimeZone('UTC')), new TransactionAmountVO('10'), $this->expenses, $this->account
        );
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $transaction = $this->repo->findById($transactionId);
        $this->assertInstanceOf(Liquidation::class, $transaction);
        $this->assertEquals($transactionId, $transaction->getId());
    }

    public function testFindByIdOrThrowException(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Liquidation(
            $this->repoLoader,
            $this->stock, new DateTime('30 minutes ago', new DateTimeZone('UTC')), new TransactionAmountVO('14'), $this->expenses, $this->account
        );
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $this->assertInstanceOf(Liquidation::class, $this->repo->findByIdOrThrowException($transactionId));
    }

    public function testFindByIdOrThrowExceptionWithNonExistingThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('expectedPersistedObjectNotFound');
        $this->repo->findByIdOrThrowException(Uuid::v4());
    }

    public function testFindByStockId(): void
    {
        parent::$loadFixtures = true;
        $transactionsCollection = $this->repo->findByStockId($this->stock2, 20, 0);
        $this->assertInstanceOf(LiquidationsCollection::class, $transactionsCollection);
        $this->assertSame(0, $transactionsCollection->count());
        new Acquisition(
            $this->repoLoader,
            $this->stock2, new DateTime('30 minutes ago', new DateTimeZone('UTC')), new TransactionAmountVO('654'), $this->expenses, $this->account
        );
        new Liquidation(
            $this->repoLoader,
            $this->stock2, new DateTime('25 minutes ago', new DateTimeZone('UTC')), new TransactionAmountVO('200'), $this->expenses, $this->account
        );
        new Liquidation(
            $this->repoLoader,
            $this->stock2, new DateTime('24 minutes ago', new DateTimeZone('UTC')), new TransactionAmountVO('400'), $this->expenses, $this->account
        );
        $transactionsCollection = $this->repo->findByStockId($this->stock2, 20, 0);
        $this->assertSame(2, $transactionsCollection->count());
        foreach ($transactionsCollection->toArray() as $transaction) {
            $this->assertSame($this->stock2->getId(), $transaction->getStock()->getId());
        }        
        $transactionsCollection = $this->repo->findByStockId($this->stock2, 1, 0);
        $this->assertSame(1, $transactionsCollection->count());
    }

    public function testFindByStockIdWithNonExistentStockReturnsEmptyArray(): void
    {
        /** @var Stock&Stub */
        $stock = $this->createStub(Stock::class);
        $stock->method('getId')->willReturn('NONEXISTENT');
        $transactionsCollection = $this->repo->findByStockId($stock, 2, 0);
        $this->assertInstanceOf(LiquidationsCollection::class, $transactionsCollection);
        $this->assertSame(0, $transactionsCollection->count());
    }

    public function testCreateIsRolledBack(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transNotPassFifoSpec');
        new Liquidation(
            $this->repoLoader,
            $this->stock, new DateTime('yesterday', new DateTimeZone('UTC')), new TransactionAmountVO('999'), $this->expenses, $this->account
        );
    }    
}
