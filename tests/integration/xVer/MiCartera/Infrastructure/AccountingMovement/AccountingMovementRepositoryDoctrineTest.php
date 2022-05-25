<?php declare(strict_types=1);

namespace Tests\integration\xVer\MiCartera\Infrastructure\AccountingMovement;

use Doctrine\Persistence\ManagerRegistry;
use Tests\unit\xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryTestAbstract;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine
 * @covers xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine
 */
class AccountingMovementRepositoryDoctrineTest extends AccountingMovementRepositoryTestAbstract
{
    private ManagerRegistry $managerRegistry;

    public function setUp(): void
    {
        // (1) boot the Symfony kernel
        $kernel = self::bootKernel(['debug' => false]);
        
        // (2) access the service container
        $container = $kernel->getContainer();
        //$entityManager = $container->get('doctrine')->getManager();
        /** @var ManagerRegistry */
        $this->managerRegistry = $container->get('doctrine');

        $this->repo = new AccountingMovementRepositoryDoctrine($this->managerRegistry);
        $this->repoTrans = new TransactionRepositoryDoctrine($this->managerRegistry);
        $repoAccount = new AccountRepositoryDoctrine($this->managerRegistry);
        $repoStock = new StockRepositoryDoctrine($this->managerRegistry);
        $this->account = $repoAccount->findByIdentifier('test@example.com');
        $this->stock = $repoStock->findById('CABK');
        $this->expenses = new MoneyVO('11.43', $this->account->getCurrency());
        $this->buyTransaction = $this->repoTrans->findOneBy(['stock' => 'CABK']);
    }

    public function tearDown(): void
    {
        foreach ($this->tearDownTrans as $transaction) {
            $this->repoTrans->remove($transaction, $this->repo);
        }
    }
}