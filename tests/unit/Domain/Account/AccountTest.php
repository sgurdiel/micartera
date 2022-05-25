<?php declare(strict_types=1);

namespace Tests\unit\Domain\Account;

use DateTimeZone;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Currency\CurrencyRepositoryInterface;

/**
 * @covers xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 */
class AccountTest extends TestCase
{
    /** @var EntityObjectRepositoryLoaderInterface&Stub */
    private EntityObjectRepositoryLoaderInterface $repoLoader;
    private Currency $currency;
    private static DateTimeZone $timezone;

    public static function setUpBeforeClass(): void
    {
        self::$timezone = new DateTimeZone("Europe/Madrid");
    }

    public function setUp(): void{
        /** @var Currency&Stub */
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('getDecimals')->willReturn(2);
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $this->repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
    }

    public function testAccountObjectIsCreated(): void
    {
        $email = 'test@example.com';
        $password = 'password';
        $repoAccount = $this->createStub(AccountRepositoryInterface::class);
        /** @var CurrencyRepositoryInterface&MockObject */
        $repoCurrency = $this->createStub(CurrencyRepositoryInterface::class);
        $repoCurrency->method('findById')->willReturn($this->currency);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [AccountRepositoryInterface::class, $repoAccount],
                [CurrencyRepositoryInterface::class, $repoCurrency]
            ])
        );
        $account = new Account($this->repoLoader, $email, $password, $this->currency, self::$timezone, ['ROLE_ADMIN']);
        $this->assertInstanceOf(Uuid::class, $account->getId());
        $this->assertTrue($account->sameId($account));
        $this->assertCount(2, $account->getRoles());
        $this->assertContains('ROLE_USER', $account->getRoles());
        $this->assertContains('ROLE_ADMIN', $account->getRoles());
        $this->assertSame($email, $account->getEmail());
        $this->assertSame($password, $account->getPassword());
        $this->assertSame(self::$timezone->getName(), $account->getTimeZone()->getName());
        $this->assertSame($this->currency, $account->getCurrency());
    }

    public function testCreateWithNonExistentCurrencyThrowsException(): void
    {
        $repoAccount = $this->createStub(AccountRepositoryInterface::class);
        /** @var CurrencyRepositoryInterface&Stub */
        $repoCurrency = $this->createStub(CurrencyRepositoryInterface::class);
        $repoCurrency->method('findById')->willReturn(null);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [AccountRepositoryInterface::class, $repoAccount],
                [CurrencyRepositoryInterface::class, $repoCurrency]
            ])
        );
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('relatedEntityNotPersisted');
        new Account($this->repoLoader, 'test@example.com', 'password', $this->currency, self::$timezone, ['ROLE_ADMIN']);
    }

    public function testCreateWithDuplicateEmailThrowsException(): void
    {
        /** @var CurrencyRepositoryInterface&Stub */
        $repoCurrency = $this->createStub(CurrencyRepositoryInterface::class);
        $repoCurrency->method('findById')->willReturn($this->createStub(Currency::class));
        /** @var AccountRepositoryInterface&Stub */
        $repoAccount = $this->createStub(AccountRepositoryInterface::class);
        $repoAccount->method('findByIdentifier')->willReturn($this->createStub(Account::class));
        $this->repoLoader->method('load')->will(
            $this->returnValueMap(
                [
                    [CurrencyRepositoryInterface::class, $repoCurrency],
                    [AccountRepositoryInterface::class, $repoAccount]
                ]
            )
        );
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('accountEmailExists');
        new Account($this->repoLoader, 'test@example.com', 'password', $this->currency, self::$timezone, ['ROLE_ADMIN']);
    }

    public function testExceptionIsThrownOnCommitFail(): void
    {
        /** @var AccountRepositoryInterface&MockObject */
        $repoAccount = $this->createMock(AccountRepositoryInterface::class);
        $repoAccount->expects($this->once())->method('persist')->willThrowException(new Exception());
        /** @var CurrencyRepositoryInterface&Stub */
        $repoCurrency = $this->createStub(CurrencyRepositoryInterface::class);
        $repoCurrency->method('findById')->willReturn($this->currency);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [AccountRepositoryInterface::class, $repoAccount],
                [CurrencyRepositoryInterface::class, $repoCurrency]
            ])
        );
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        new Account($this->repoLoader, 'test@example.com', 'password', $this->currency, self::$timezone, ['ROLE_ADMIN']);
    }
}
