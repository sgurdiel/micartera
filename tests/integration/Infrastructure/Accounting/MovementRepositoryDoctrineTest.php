<?php declare(strict_types=1);

namespace Tests\integration\Infrastructure\Accounting;

use DateTime;
use DateTimeZone;
use Symfony\Component\Uid\Uuid;
use Tests\integration\IntegrationTestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Accounting\Movement;
use xVer\MiCartera\Domain\Accounting\MovementRepositoryInterface;
use xVer\MiCartera\Domain\Accounting\MovementsCollection;
use xVer\MiCartera\Domain\Accounting\SummaryVO;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\Adquisition;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Accounting\MovementRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\Accounting\MovementRepositoryDoctrine
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Accounting\Movement
 * @uses xVer\MiCartera\Domain\Accounting\MovementsCollection
 * @uses xVer\MiCartera\Domain\Accounting\SummaryVO
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Adquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Criteria\FiFoCriteria
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Liquidation
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine 
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\AdquisitionRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine
 */
class MovementRepositoryDoctrineTest extends IntegrationTestCase
{
    private MovementRepositoryInterface $repo;
    private Account $account;
    private Stock $stock;
    private MoneyVO $expenses;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->repo = new MovementRepositoryDoctrine(self::$registry);
        $repoAccount = new AccountRepositoryDoctrine(self::$registry);
        $repoStock = new StockRepositoryDoctrine(self::$registry);
        $this->account = $repoAccount->findByIdentifier('test@example.com');
        $this->stock = $repoStock->findById('SAN');
        $this->expenses = new MoneyVO('11.43', $this->account->getCurrency());
    }

    public function testFindByIdOrThowException(): void
    {
        self::$loadFixtures = true;
        $adquisition = new Adquisition($this->repoLoader, $this->stock, new DateTime('30 mins ago', new DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $liquidation = new Liquidation($this->repoLoader, $this->stock, new DateTime('20 mins ago', new DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $movement = $this->repo->findByIdOrThrowException($adquisition->getId(), $liquidation->getId());
        $this->assertInstanceOf(Movement::class, $movement);
        $this->assertSame($adquisition, $movement->getAdquisition());
        $this->assertSame($liquidation, $movement->getLiquidation());
    }

    public function testFindByIdOrThowExceptionWhenNotFoundThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('expectedPersistedObjectNotFound');
        $this->repo->findByIdOrThrowException(Uuid::v4(), Uuid::v4());
    }

    public function testFindByAccountAndYear(): void
    {
        self::$loadFixtures = true;
        $movementCollection = $this->repo->findByAccountAndYear($this->account, (int) (new DateTime('now', new DateTimeZone('UTC')))->format('Y'));
        $this->assertInstanceOf(MovementsCollection::class, $movementCollection);
        $this->assertSame(0, $movementCollection->count());
        $adquisition = new Adquisition($this->repoLoader, $this->stock, new DateTime('30 mins ago', new DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $liquidation = new Liquidation($this->repoLoader, $this->stock, new DateTime('20 mins ago', new DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $movementCollection = $this->repo->findByAccountAndYear($this->account, (int) (new DateTime('now', new DateTimeZone('UTC')))->format('Y'));
        $this->assertSame(1, $movementCollection->count());
        $this->assertTrue($adquisition->sameId($movementCollection->offsetGet(0)->getAdquisition()));
        $this->assertTrue($liquidation->sameId($movementCollection->offsetGet(0)->getLiquidation()));
    }

    public function testFindByAccountStockAdquisitionDateAfter(): void
    {
        self::$loadFixtures = true;
        $movementCollection = $this->repo->findByAccountStockAdquisitionDateAfter($this->account, $this->stock, new DateTime('yesterday', new DateTimeZone('UTC')));
        $this->assertInstanceOf(MovementsCollection::class, $movementCollection);
        $this->assertSame(0, $movementCollection->count());
        $adquisition = new Adquisition($this->repoLoader, $this->stock, new DateTime('48 hours ago', new DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $liquidation = new Liquidation($this->repoLoader, $this->stock, new DateTime('47 hours ago', new DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $movementCollection = $this->repo->findByAccountStockAdquisitionDateAfter($this->account, $this->stock, new DateTime('yesterday', new DateTimeZone('UTC')));
        $this->assertSame(0, $movementCollection->count());
        $movementCollection = $this->repo->findByAccountStockAdquisitionDateAfter($this->account, $this->stock, new DateTime('3 days ago', new DateTimeZone('UTC')));
        $this->assertSame(1, $movementCollection->count());
        $this->assertTrue($adquisition->sameId($movementCollection->offsetGet(0)->getAdquisition()));
        $this->assertTrue($liquidation->sameId($movementCollection->offsetGet(0)->getLiquidation()));
    }

    public function testAccountingSummaryByAccount(): void
    {
        $summary = $this->repo->accountingSummaryByAccount($this->account, (int) (new DateTime('now', new DateTimeZone('UTC')))->format('Y'));
        $this->assertInstanceOf(SummaryVO::class, $summary);
    }

    public function testRemoveDoesNotWriteToDatabase(): void
    {
        self::$loadFixtures = true;
        $adquisition = new Adquisition($this->repoLoader, $this->stock, new DateTime('30 mins ago', new DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $liquidation = new Liquidation($this->repoLoader, $this->stock, new DateTime('20 mins ago', new DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $movement = $this->repo->findByIdOrThrowException($adquisition->getId(), $liquidation->getId());
        $this->repo->remove($movement);
        parent::detachEntity($movement);
        $movement = $this->repo->findByIdOrThrowException($adquisition->getId(), $liquidation->getId());
        $this->assertInstanceOf(Movement::class, $movement);
        $this->assertSame($adquisition, $movement->getAdquisition());
        $this->assertSame($liquidation, $movement->getLiquidation());
    }
}