<?php declare(strict_types=1);

namespace Tests\integration\xVer\MiCartera\Infrastructure\Transaction;

use Doctrine\Persistence\ManagerRegistry;
use Tests\unit\xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryTestAbstract;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine
 * @covers xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine
 * @uses xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 */
class TransactionRepositoryDoctrineTest extends TransactionRepositoryTestAbstract
{
    private ManagerRegistry $managerRegistry;

    public static function setUpBeforeClass(): void
    {
        self::$timezone = new \DateTimeZone("Europe/Madrid");
    }

    public function setUp(): void
    {
        // (1) boot the Symfony kernel
        $kernel = self::bootKernel(['debug' => false]);
        
        // (2) access the service container
        $container = $kernel->getContainer();
        //$entityManager = $container->get('doctrine')->getManager();
        /** @var ManagerRegistry */
        $this->managerRegistry = $container->get('doctrine');

        $this->repo = new TransactionRepositoryDoctrine($this->managerRegistry);
        $this->accountingMovementRepo = new AccountingMovementRepositoryDoctrine($this->managerRegistry);
        $repoCurrency = new CurrencyRepositoryDoctrine($this->managerRegistry);
        $repoAccount = new AccountRepositoryDoctrine($this->managerRegistry);
        $repoStock = new StockRepositoryDoctrine($this->managerRegistry);
        $this->currency = $repoCurrency->findById('EUR');
        $this->account = $repoAccount->findByIdentifier('test@example.com');
        $this->account2 =$repoAccount->findByIdentifier('test_other@example.com');
        $this->stock = $repoStock->findById('CABK');
        $this->stock2 = $repoStock->findById('SAN');
        $this->stock3 = $repoStock->findById('ROVI');
        $this->expenses = new MoneyVO('11.43', $this->account->getCurrency());
        $this->tearDownTrans = [];
    }

    public function tearDown(): void
    {
        foreach ($this->tearDownTrans as $transaction) {
            $this->repo->remove($transaction, $this->accountingMovementRepo);
        }
    }
}