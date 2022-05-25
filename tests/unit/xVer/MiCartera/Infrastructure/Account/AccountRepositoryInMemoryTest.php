<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\Account;

use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Infrastructure\Account\AccountRepositoryInMemory
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInMemory
 */
class AccountRepositoryInMemoryTest extends AccountRepositoryTestAbstract
{
    public static function setUpBeforeClass(): void
    {
        self::$email = "test4@example.com";
        $currency = new Currency('EUR', '€', 2);
        self::$repoCurrency = new CurrencyRepositoryInMemory();
        $currency = self::$repoCurrency->add($currency);
        self::$account = new Account(self::$email, "password", $currency, new \DateTimeZone("Europe/Madrid"), ['ROLE_USER']);
        self::$repo = new AccountRepositoryInMemory();
    }
}
