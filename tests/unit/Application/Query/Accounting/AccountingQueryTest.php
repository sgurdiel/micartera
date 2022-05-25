<?php declare(strict_types=1);

namespace Tests\unit\Application\Query\Accounting;

use DateTime;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Application\Query\Accounting\AccountingQuery;
use xVer\MiCartera\Application\Query\Accounting\AccountingDTO;
use xVer\MiCartera\Domain\Accounting\MovementsCollection;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Accounting\SummaryVO;
use xVer\MiCartera\Infrastructure\Accounting\MovementRepositoryDoctrine;
use xVer\MiCartera\Domain\Accounting\MovementRepositoryInterface;

/**
 * @covers xVer\MiCartera\Application\Query\Accounting\AccountingQuery
 * @uses xVer\MiCartera\Application\Query\Accounting\AccountingDTO
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
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
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
