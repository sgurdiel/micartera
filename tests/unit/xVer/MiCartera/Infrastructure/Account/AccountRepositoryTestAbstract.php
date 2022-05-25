<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\Account;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryInterface;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInterface;

abstract class AccountRepositoryTestAbstract extends KernelTestCase implements AccountRepositoryTestInterface
{
    protected static AccountRepositoryInterface $repo;
    protected static CurrencyRepositoryInterface $repoCurrency;
    protected static Account $account;
    protected static string $email;

    public function testAccountIsAdded(): AccountRepositoryInterface
    {
        $account = self::$repo->add(self::$account, self::$repoCurrency);
        $this->assertInstanceOf(Account::class, $account);
        return self::$repo;
    }

    /** @depends testAccountIsAdded */
    public function testAccountIsFoundByEmail(AccountRepositoryInterface $repo): AccountRepositoryInterface
    {
        $account = $repo->findByIdentifier(self::$email);
        $this->assertInstanceOf(Account::class, $account);
        $this->assertSame(self::$email, $account->getEmail());
        return $repo;
    }

    /** @depends testAccountIsAdded */
    public function testAddingAccountWithExistingEmailThrowsException(AccountRepositoryInterface $repo): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('accountEmailExists');
        $account = $repo->add(self::$account, self::$repoCurrency);
    }

    /** @depends testAccountIsAdded */
    public function testAddingAccountWithNotPersistedCurrencyThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('relatedEntityNotPersisted');
        $account = new Account('test2@example.com', 'password', new Currency('GBP', '£', 2), new \DateTimeZone('UTC'));
        $account = self::$repo->add($account, self::$repoCurrency);
    }
}