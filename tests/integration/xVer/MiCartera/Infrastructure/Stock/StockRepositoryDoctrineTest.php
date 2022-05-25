<?php declare(strict_types=1);

namespace Tests\integration\xVer\MiCartera\Infrastructure\Stock;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tests\unit\xVer\MiCartera\Infrastructure\Stock\StockRepositoryTestAbstract;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine 
 */
class StockRepositoryDoctrineTest extends StockRepositoryTestAbstract
{
    protected StockRepositoryDoctrine $repo;
    private ManagerRegistry $managerRegistry;
    private AccountingMovementRepositoryDoctrine $repoAccountingMovement;
    /** @var Transaction[] */
    private array $tearDownTrans;

    public function setUp(): void
    {
        // (1) boot the Symfony kernel
        $kernel = self::bootKernel(['debug' => false]);
        
        // (2) access the service container
        $container = $kernel->getContainer();
        //$entityManager = $container->get('doctrine')->getManager();
        /** @var ManagerRegistry */
        $this->managerRegistry = $container->get('doctrine');

        $this->repo = new StockRepositoryDoctrine($this->managerRegistry);
        $this->repoCurrency = new CurrencyRepositoryDoctrine($this->managerRegistry);
        $this->repoAccountingMovement = new AccountingMovementRepositoryDoctrine($this->managerRegistry);
        $this->currency = $this->repoCurrency->findById('EUR');
        $this->currency2 = $this->repoCurrency->findById('USD');
        $this->code= 'ABCD';
        $this->name = 'ABCD Name';
        $this->price = new StockPriceVO('2.6632', $this->currency);
        $this->stock = new Stock($this->code, $this->name, $this->price);
        $this->tearDownStocks = [];
        $this->tearDownTrans = [];
    }

    public function tearDown(): void
    {
        $repoTrans = new TransactionRepositoryDoctrine($this->managerRegistry);
        foreach ($this->tearDownStocks as $stock) {
            $this->repo->remove($stock, $repoTrans);
        }
        foreach ($this->tearDownTrans as $transaction) {
            $this->repoTrans->remove($transaction, $this->repoAccountingMovement);
        }
    }
    
    public function testStockIsAddedUpdatedAndRemoved(): void
    {           
        $this->repoTrans = new TransactionRepositoryDoctrine($this->managerRegistry);
        parent::testStockIsAddedUpdatedAndRemoved();
    }

    public function testRemovingStockHavingTransactionsThrowsException(): void
    {
        $this->repoTrans = new TransactionRepositoryDoctrine($this->managerRegistry);
        $repoAccount = new AccountRepositoryDoctrine($this->managerRegistry);
        $account = $repoAccount->findByIdentifier('test@example.com');
        $this->code2 = 'CABK';
        $stock = $this->repo->findById($this->code2);
        $expenses = new MoneyVO('5.44', $this->currency);
        $buyTrans = new Transaction(Transaction::TYPE_BUY, $stock, new \DateTime('yesterday', new \DateTimeZone('UTC')), 400, $expenses, $account);
        $buyTrans = $this->repoTrans->add($buyTrans, $this->repoAccountingMovement);
        $this->tearDownTrans[] = $buyTrans;
        parent::testRemovingStockHavingTransactionsThrowsException();
    }

    public function testQueryBuilderForTransactionForm(): void
    {
        $this->assertInstanceOf(QueryBuilder::class, $this->repo->queryBuilderForTransactionForm($this->currency));
    }
}
