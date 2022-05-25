<?php declare(strict_types=1);

namespace Tests\integration\xVer\MiCartera\Infrastructure\Account;

use Doctrine\Persistence\ManagerRegistry;
use Tests\unit\xVer\MiCartera\Infrastructure\Account\AccountRepositoryTestAbstract;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryInterface;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 */
class AccountRepositoryDoctrineTest extends AccountRepositoryTestAbstract
{
    private static  ManagerRegistry $managerRegistry;

    public static function setUpBeforeClass(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel(['debug' => false]);

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        /** @var ManagerRegistry $managerRegistry */
        self::$managerRegistry = $container->get(ManagerRegistry::class);

        self::$email = 'test4@example.com';
        self::$repoCurrency = new CurrencyRepositoryDoctrine(self::$managerRegistry);
        $currency = self::$repoCurrency->findById('EUR');
        self::$account = new Account(self::$email, "password", $currency, new \DateTimeZone("Europe/Madrid"), ['ROLE_USER']);
    }

    public static function tearDownAfterClass(): void
    {
        self::$repo = new AccountRepositoryDoctrine(self::$managerRegistry);
        $account = self::$repo->findByIdentifier('test4@example.com');
        self::$managerRegistry->getManager()->remove($account);
        self::$managerRegistry->getManager()->flush();
    }

    public function testAccountIsAdded(): AccountRepositoryInterface
    {
        self::$repo = new AccountRepositoryDoctrine(self::$managerRegistry);
        return parent::testAccountIsAdded();
    }
}
