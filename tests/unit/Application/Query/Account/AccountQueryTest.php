<?php declare(strict_types=1);

namespace Tests\unit\Application\Query\Account;

use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Application\Query\Account\AccountQuery;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;

/**
 * @covers xVer\MiCartera\Application\Query\Account\AccountQuery
 */
class AccountQueryTest extends KernelTestCase
{   
    public function testByIdentifierQuerySucceeds(): void
    {
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [AccountRepositoryInterface::class, $repoAccount]
            ])
        );
        $query = new AccountQuery($repoLoader);
        $account = $query->byIdentifier('test@example.com');
        $this->assertInstanceOf(Account::class, $account);
    }
}
