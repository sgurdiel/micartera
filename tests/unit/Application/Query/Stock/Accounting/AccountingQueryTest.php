<?php declare(strict_types=1);

namespace Tests\unit\Application\Query\Stock\Accounting;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Application\Query\Stock\Accounting\AccountingQuery;
use xVer\MiCartera\Application\Query\Stock\Accounting\AccountingDTO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Accounting\MovementsCollection;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Accounting\SummaryVO;
use xVer\MiCartera\Infrastructure\Stock\Accounting\MovementRepositoryDoctrine;
use xVer\MiCartera\Domain\Stock\Accounting\MovementRepositoryInterface;

/**
 * @covers xVer\MiCartera\Application\Query\Stock\Accounting\AccountingQuery
 * @uses xVer\MiCartera\Application\Query\Stock\Accounting\AccountingDTO
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 */
class AccountingQueryTest extends KernelTestCase
{
    /**
     * @dataProvider displayedYear
     */
    public function testByAccountYearCommandSucceeds($displayedYear): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getTimeZone')->willReturn((new DateTime('now', new DateTimeZone('UTC')))->getTimezone());
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        $repoAccount->method('findByIdentifierOrThrowException')->willReturn($account);
        /** @var MovementRepositoryDoctrine&Stub */
        $repoAccountingMovement = $this->createStub(MovementRepositoryDoctrine::class);
        $repoAccountingMovement->method('findByAccountAndYear')->willReturn(
            $this->createStub(MovementsCollection::class)
        );
        $repoAccountingMovement->method('accountingSummaryByAccount')->willReturn($this->createStub(SummaryVO::class));
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [AccountRepositoryInterface::class, $repoAccount],
                [MovementRepositoryInterface::class, $repoAccountingMovement]
            ])
        );
        $query = new AccountingQuery($repoLoader);
        $accountingDTO = $query->byAccountYear('test@example.com', $displayedYear);
        $this->assertInstanceOf(AccountingDTO::class, $accountingDTO);
    }

    public static function displayedYear(): array
    {
        return [
            [null],
            [(int) (new DateTime('now'))->format('Y')]
        ];
    }
}
