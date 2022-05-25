<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Application\Command;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Application\Command\AddAccountCommand;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Application\Command\AddAccountCommand
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryInMemory
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryTrait
 */
class AccountCommandTest extends TestCase
{
    private static \DateTimeZone $timezone;

    public static function setUpBeforeClass(): void
    {
        self::$timezone = new \DateTimeZone("Europe/Madrid");
    }

    public function testCommandExecutionSuccessfuly(): AccountRepositoryInMemory
    {
        $repo = new AccountRepositoryInMemory();
        $repoCurrency = new CurrencyRepositoryInMemory();
        $currency = new Currency('EUR', '€', 2);
        $repoCurrency->add($currency);
        $email = "test@example.com";
        $password = "password";
        $account = new Account($email, $password, $currency, self::$timezone, ['ROLE_USER']);
        $command = new AddAccountCommand();
        $account = $command->execute($repo, $repoCurrency, $account);
        $this->assertSame($email, $account->getEmail());

        return $repo;
    }

    /**
     * @depends testCommandExecutionSuccessfuly
     */
    public function testCommandExecutionFailure(AccountRepositoryInMemory $repo): void
    {
        $this->expectException(DomainException::class);
        $repoCurrency = new CurrencyRepositoryInMemory();
        $currency = new Currency('EUR', '€', 2);
        $repoCurrency->add($currency);
        $email = "test@example.com";
        $password = "password";
        $account = new Account($email, $password, $currency, self::$timezone, ['ROLE_USER']);
        $command = new AddAccountCommand();
        $command->execute($repo, $repoCurrency, $account);
    }
}
