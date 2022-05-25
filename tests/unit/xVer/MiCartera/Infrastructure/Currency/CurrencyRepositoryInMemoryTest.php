<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\Currency;

use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInMemory
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 */
class CurrencyRepositoryInMemoryTest extends CurrencyRepositoryTestAbstract
{
    public static function setUpBeforeClass(): void
    {
        self::$iso3 = 'GBP';
        self::$currency = new Currency(self::$iso3, '£', 2);
        self::$repo = new CurrencyRepositoryInMemory();
    }
}
