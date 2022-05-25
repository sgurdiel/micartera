<?php declare(strict_types=1);

namespace Tests\integration\xVer\MiCartera\Infrastructure\AccountingMovement;

use Doctrine\Persistence\ManagerRegistry;
use Tests\unit\xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContractTestAbstract;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @uses xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryTrait
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 */
class AccountingMovementFifoContractDoctrineTest extends AccountingMovementFifoContractTestAbstract
{
    private static ManagerRegistry $managerRegistry;

    public static function setUpBeforeClass(): void
    {
        // (1) boot the Symfony kernel
        $kernel = self::bootKernel(['debug' => false]);
        
        // (2) access the service container
        $container = $kernel->getContainer();
        //$entityManager = $container->get('doctrine')->getManager();
        /** @var ManagerRegistry */
        self::$managerRegistry = $container->get('doctrine');
    }

    public function setUp(): void
    {
        $this->repoAccountingMovement = new AccountingMovementRepositoryDoctrine(self::$managerRegistry);
        $this->repoTrans = new TransactionRepositoryDoctrine(self::$managerRegistry);
        $repoAccount = new AccountRepositoryDoctrine(self::$managerRegistry);
        $this->account = $repoAccount->findByIdentifier('test@example.com');
        $repoStock = new StockRepositoryDoctrine(self::$managerRegistry);
        $this->stock = $repoStock->findById('SAN');
        $this->expenses = new MoneyVO('4.55', $this->account->getCurrency());
    }
}