<?php declare(strict_types=1);

namespace Tests\integration\xVer\MiCartera\Infrastructure\Currency;

use Doctrine\Persistence\ManagerRegistry;
use Tests\unit\xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryTestAbstract;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInterface;

/**
 * @covers xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 */
class CurrencyRepositoryDoctrineTest extends CurrencyRepositoryTestAbstract
{
    private static ManagerRegistry $managerRegistry;

    public static function setUpBeforeClass(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel(['debug' => false]);

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        /** @var ManagerRegistry $managerRegistry */
        self::$managerRegistry = $container->get(ManagerRegistry::class);

        self::$iso3 = 'GBP';
        self::$currency = new Currency(self::$iso3, '£', 2);
    }

    public static function tearDownAfterClass(): void
    {
        $currency = self::$managerRegistry->getManagerForClass(Currency::class)->getRepository(Currency::class)->findOneBy(['iso3' => 'GBP']);
        self::$managerRegistry->getManagerForClass(Currency::class)->remove($currency);
        self::$managerRegistry->getManagerForClass(Currency::class)->flush();
    }

    public function testCurrencyIsAdded(): CurrencyRepositoryInterface
    {
        self::$repo = new CurrencyRepositoryDoctrine(self::$managerRegistry);
        return parent::testCurrencyIsAdded();
    }
}
