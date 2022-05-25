<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Domain\Account;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;

/**
 * @covers xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO 
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 */
class AccountTest extends TestCase
{
    private static Currency $currency;
    private static \DateTimeZone $timezone;

    public static function setUpBeforeClass(): void
    {
        self::$currency = new Currency('EUR', '€', 2);
        self::$timezone = new \DateTimeZone("Europe/Madrid");
    }

    public function testAccountObjectIsCreated(): void
    {
        $email = "test@example.com";
        $password = "password";
        $account = new Account($email, $password, self::$currency, self::$timezone, ['ROLE_ADMIN']);
        $this->assertInstanceOf(Uuid::class, $account->getId());
        $this->assertTrue($account->sameId($account));
        $this->assertCount(2, $account->getRoles());
        $this->assertContains('ROLE_USER', $account->getRoles());
        $this->assertContains('ROLE_ADMIN', $account->getRoles());
        $this->assertSame($email, $account->getEmail());
        $this->assertSame($password, $account->getPassword());
        $this->assertSame(self::$timezone->getName(), $account->getTimeZone()->getName());
        $this->assertSame(self::$currency, $account->getCurrency());
    }

    public function testUpdateAccountWithInvalidEmailThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('error_invalid_email');
        $account = new Account("test@example.com", "password", self::$currency, self::$timezone, ['ROLE_USER']);
        $invalid_email = "test@example";
        $account->setEmail($invalid_email);
    }

    public function testCreateAccountWithInvalidRoleThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('invalidUserRole');
        $account = new Account("test@example.com", "password", self::$currency, self::$timezone, ['ROLE_NOEXISTS']);
        unset($account);
    }

    public function testSetPassword(): void
    {
        $account = new Account("test@example.com", "password", self::$currency, self::$timezone, ['ROLE_USER']);
        $password = "d77f3Sljj4d0s";
        $account->setPassword($password);
        $this->assertSame($password, $account->getPassword());
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $account = new Account("test@example.com", "password", self::$currency, self::$timezone, ['ROLE_USER']);
        $stock = new Stock('ABCD', 'ABCD Name', new StockPriceVO('44.3211', $account->getCurrency()));
        $account->sameId($stock);
    }
}
