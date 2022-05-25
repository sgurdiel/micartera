<?php declare(strict_types=1);

namespace Tests\integration\Infrastructure\Account;

use DateTimeZone;
use Tests\integration\IntegrationTestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Adquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Criteria\FifoCriteria
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\AdquisitionRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine
 */
class AccountRepositoryDoctrineTest extends IntegrationTestCase
{
    private AccountRepositoryDoctrine $repoAccount;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->repoAccount = new AccountRepositoryDoctrine(self::$registry);
    }

    public function testAccountIsPersisted(): void
    {
        $account = $this->repoAccount->persist(
            new Account(
                $this->repoLoader,
                'test4@example.com',
                "password",
                (new CurrencyRepositoryDoctrine(self::$registry))->findByIdOrThrowException('EUR'),
                new DateTimeZone("Europe/Madrid"),
                ['ROLE_USER']
            )
        );
        $accountId = $account->getId();
        parent::detachEntity($account);
        $this->assertInstanceOf(Account::class, $this->repoAccount->findById($accountId));
    }

    public function testFindByIdentifierOrThrowsException(): void
    {
        $email = 'test@example.com';
        $account = $this->repoAccount->findByIdentifierOrThrowException($email);
        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals($email, $account->getEmail());
    }

    public function testFindByIdentifierOrThrowsExceptionWhenNotFoundWillThrowException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('expectedPersistedObjectNotFound');
        $this->repoAccount->findByIdentifierOrThrowException('nonexistent@example.com');
    }

    public function testFindByIdentifier(): void
    {
        $email = 'test@example.com';
        $account = $this->repoAccount->findByIdentifier($email);
        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals($email, $account->getEmail());
    }
}
