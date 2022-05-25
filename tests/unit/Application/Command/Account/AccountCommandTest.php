<?php declare(strict_types=1);

namespace Tests\unit\Application\Command\Account;

use DateTimeZone;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Application\Command\Account\AccountCommand;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Currency\CurrencyRepositoryInterface;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine;
use xVer\Symfony\Bundle\BaseAppBundle\Domain\Account\AccountRepositoryInterface as AccountAccountRepositoryInterface;

/**
 * @covers xVer\MiCartera\Application\Command\Account\AccountCommand
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Domain\Currency\CurrenciesCollection
 */
class AccountCommandTest extends TestCase
{
    private static DateTimeZone $timezone;
    private static string $email;
    private static string $password;
    private static array $roles;
    private static bool $agreeTerms;
    private static string $currencyIso3;
    /** @var EntityObjectRepositoryLoaderInterface&Stub */
    private EntityObjectRepositoryLoaderInterface $repoLoader;

    public static function setUpBeforeClass(): void
    {
        self::$timezone = new DateTimeZone("Europe/Madrid");
        self::$email = 'test@example.com';
        self::$password = "password";
        self::$roles = ['ROLE_USER'];
        self::$agreeTerms = true;
        self::$currencyIso3 = 'EUR';
    }

    public function setUp(): void
    {
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $this->repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
    }

    public function testCreateCommandExecutionSuccessfuly(): void
    {
        $currency = $this->createStub(Currency::class);
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        /** @var CurrencyRepositoryDoctrine&Stub */
        $repoCurrency = $this->createStub(CurrencyRepositoryDoctrine::class);
        $repoCurrency->method('findByIdOrThrowException')->willReturn($currency);
        $repoCurrency->method('findById')->willReturn($currency);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [AccountRepositoryInterface::class, $repoAccount],
                [AccountAccountRepositoryInterface::class, $repoAccount],
                [CurrencyRepositoryInterface::class, $repoCurrency]
            ])
        );
        $command = new AccountCommand($this->repoLoader);
        $account = $command->create(
            self::$email,
            self::$password,
            self::$currencyIso3,
            self::$timezone,
            self::$roles,
            self::$agreeTerms
        );
        $this->assertInstanceOf(Account::class, $account);
    }

    public function testCreatCommandNoAgreeTermsThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('mustAgreeTerms');
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        $repoCurrency = $this->createStub(CurrencyRepositoryDoctrine::class);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [AccountRepositoryInterface::class, $repoAccount],
                [CurrencyRepositoryInterface::class, $repoCurrency]
            ])
        );
        $command = new AccountCommand($this->repoLoader);
        $command->create(
            self::$email,
            self::$password,
            self::$currencyIso3,
            self::$timezone,
            self::$roles,
            false
        );
    }

    public function testCreateCommandBadCurrencyThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('expectedPersistedObjectNotFound');
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        /** @var CurrencyRepositoryDoctrine&Stub */
        $repoCurrency = $this->createStub(CurrencyRepositoryDoctrine::class);
        $repoCurrency->method('findByIdOrThrowException')->willThrowException(new \Exception('expectedPersistedObjectNotFound'));
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [AccountRepositoryInterface::class, $repoAccount],
                [CurrencyRepositoryInterface::class, $repoCurrency]
            ])
        );
        $command = new AccountCommand($this->repoLoader);
        $command->create(
            self::$email,
            self::$password,
            self::$currencyIso3,
            self::$timezone,
            self::$roles,
            self::$agreeTerms
        );
    }

    public function testCreateCommandBadNewAccountThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $currency = $this->createStub(Currency::class);
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        /** @var CurrencyRepositoryDoctrine&Stub */
        $repoCurrency = $this->createStub(CurrencyRepositoryDoctrine::class);
        $repoCurrency->method('findByIdOrThrowException')->willReturn($currency);
        $repoCurrency->method('findById')->willReturn($currency);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [AccountRepositoryInterface::class, $repoAccount],
                [CurrencyRepositoryInterface::class, $repoCurrency]
            ])
        );
        $command = new AccountCommand($this->repoLoader);
        $command->create(
            'invalidemail',
            self::$password,
            self::$currencyIso3,
            self::$timezone,
            self::$roles,
            self::$agreeTerms
        );
    }
}
