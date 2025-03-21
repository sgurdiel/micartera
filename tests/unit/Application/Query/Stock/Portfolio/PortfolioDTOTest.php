<?php declare(strict_types=1);

namespace Tests\unit\Application\Query\Stock\Portfolio;

use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Application\Query\Stock\Portfolio\PortfolioDTO;
use xVer\MiCartera\Domain\Stock\Portfolio\SummaryVO;
use xVer\MiCartera\Domain\Stock\Transaction\Acquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection;

/**
 * @covers xVer\MiCartera\Application\Query\Stock\Portfolio\PortfolioDTO
 * @uses xVer\MiCartera\Domain\MoneyVO
 */
class PortfolioDTOTest extends KernelTestCase
{    
    public function testPortfolio(): void
    {
        $currency = $this->createStub(Currency::class);
        /** @var Account&Stub */
        $account = $this->createStub(Account::class);
        $account->method('getCurrency')->willReturn($currency);
        /** @var AcquisitionsCollection&Stub */
        $outstandingPositionsCollection = $this->createStub(AcquisitionsCollection::class);
        $outstandingPositionsCollection->method('offsetGet')->willReturn(
            $this->createStub(Acquisition::class)
        );
        /** @var SummaryVO&Stub */
        $summary = $this->createStub(SummaryVO::class);
        $portfolio = new PortfolioDTO(
            $account,
            $outstandingPositionsCollection,
            $summary
        );
        $this->assertSame($account, $portfolio->getAccount());
        $this->assertSame($outstandingPositionsCollection, $portfolio->getCollection());
        $this->assertSame($summary, $portfolio->getSummary());
        $this->assertNotNull($portfolio->getPositionAcquisitionExpenses(0));
        $this->assertNotNull($portfolio->getPositionAcquisitionPrice(0));
        $this->assertNotNull($portfolio->getPositionMarketPrice(0));
        $this->assertNotNull($portfolio->getPositionProfitPrice(0));
        $this->assertNotNull($portfolio->getPositionProfitPercentage(0));
    }

    public function testEmptyPortfolio(): void
    {
        $currency = $this->createStub(Currency::class);
        /** @var Account&Stub */
        $account = $this->createStub(Account::class);
        $account->method('getCurrency')->willReturn($currency);
        $outstandingPositionsCollection = new AcquisitionsCollection([]);
        /** @var SummaryVO&Stub */
        $summary = $this->createStub(SummaryVO::class);
        $portfolio = new PortfolioDTO(
            $account,
            $outstandingPositionsCollection,
            $summary
        );
        $this->assertSame($account, $portfolio->getAccount());
        $this->assertSame($outstandingPositionsCollection, $portfolio->getCollection());
        $this->assertSame($summary, $portfolio->getSummary());
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('collectionInvalidOffsetPosition');
        $this->assertNull($portfolio->getPositionAcquisitionExpenses(0));        
    }
}
