<?php declare(strict_types=1);

namespace Tests\unit\Application\Query\Stock\Accounting;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Application\Query\Stock\Accounting\AccountingDTO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Accounting\SummaryVO;
use xVer\MiCartera\Domain\Stock\Accounting\Movement;
use xVer\MiCartera\Domain\Stock\Accounting\MovementsCollection;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;

/**
 * @covers xVer\MiCartera\Application\Query\Stock\Accounting\AccountingDTO
 * @uses xVer\MiCartera\Domain\Stock\Accounting\Movement
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Accounting\MovementsCollection
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 */
class AccountingDTOTest extends KernelTestCase
{  
    /** @var Account&Stub */
    private Account $account;

    public function setUp(): void
    {
        /** @var Currency&Stub */
        $currency = $this->createStub(Currency::class);
        $currency->method('getDecimals')->willReturn(2);
        $currency->method('sameId')->willReturn(true);
        /** @var Account&Stub */
        $this->account = $this->createStub(Account::class);
        $this->account->method('getCurrency')->willReturn($currency);
        $this->account->method('getTimeZone')->willReturn(new DateTimeZone('UTC'));
        
    }

    public function testAccountingDTO(): void
    {
        $displayedYear = ((int) (new DateTime('1 year ago'))->format('Y'));
        /** @var MovementsCollection&Stub */
        $accountingMovementsCollection = $this->createStub(MovementsCollection::class);
        $accountingMovementsCollection->method('offsetExists')->willReturn(true);
        $accountingMovementsCollection->method('offsetGet')->willReturn($this->createStub(Movement::class));
        $summary = $this->createStub(SummaryVO::class);
        $accountingDTO = new AccountingDTO(
            $this->account,
            $accountingMovementsCollection,
            $displayedYear,
            $summary
        );
        $this->assertInstanceOf(MovementsCollection::class, $accountingDTO->getCollection());
        $this->assertSame($this->account, $accountingDTO->getAccount());
        $this->assertSame((int) (new DateTime('now', $this->account->getTimeZone()))->format('Y'), $accountingDTO->getCurrentYear());
        $this->assertSame($displayedYear, $accountingDTO->getDisplayedYear());
        $this->assertSame($summary, $accountingDTO->getSummary());
        $this->assertInstanceOf(MoneyVO::class, $accountingDTO->getMovementAcquisitionExpense(0));
        $this->assertInstanceOf(MoneyVO::class, $accountingDTO->getMovementAcquisitionPrice(0));
        $this->assertInstanceOf(MoneyVO::class, $accountingDTO->getMovementLiquidationExpense(0));
        $this->assertInstanceOf(MoneyVO::class, $accountingDTO->getMovementLiquidationPrice(0));
        $this->assertSame('', $accountingDTO->getMovementProfitPercentage(0));
        $this->assertInstanceOf(MoneyVO::class, $accountingDTO->getMovementProfitPrice(0));
    }

    public function testAccountingDTOWithNoAccountingMovements(): void
    {
        $accountingDTO = new AccountingDTO(
            $this->account,
            new MovementsCollection([]),
            (int) (new DateTime('now'))->format('Y'),
            $this->createStub(SummaryVO::class)
        );
        $this->assertInstanceOf(MovementsCollection::class, $accountingDTO->getCollection());
        $this->assertSame(0, $accountingDTO->getCollection()->count());
    }

    public function testNonObjectAccountingMovementsArgumentThrowsExeption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AccountingDTO(
            $this->account,
            new MovementsCollection([1]),
            (int) (new DateTime('now'))->format('Y'),
            $this->createStub(SummaryVO::class)
        );
    }

    public function testInvalidAccountingMovementsArgumentThrowsExeption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AccountingDTO(
            $this->account,
            new MovementsCollection([new \stdClass]),
            (int) (new DateTime('now'))->format('Y'),
            $this->createStub(SummaryVO::class)
        );
    }

    public function testSetCollectionKeyWithInvalidOffsetThrowsException(): void
    {
        $accountingDTO = new AccountingDTO(
            $this->account,
            new MovementsCollection([]),
            (int) (new DateTime('now'))->format('Y'),
            $this->createStub(SummaryVO::class)
        );
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('collectionInvalidOffsetPosition');
        $accountingDTO->getMovementAcquisitionPrice(1);
    }
}
