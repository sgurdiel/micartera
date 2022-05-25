<?php declare(strict_types=1);

namespace Tests\unit\Application\Query\Portfolio;

use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Application\Query\Portfolio\PortfolioDTO;
use xVer\MiCartera\Domain\Portfolio\SummaryVO;
use xVer\MiCartera\Domain\Stock\Transaction\Adquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection;

/**
 * @covers xVer\MiCartera\Application\Query\Portfolio\PortfolioDTO
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
        /** @var AdquisitionsCollection&Stub */
        $outstandingPositionsCollection = $this->createStub(AdquisitionsCollection::class);
        $outstandingPositionsCollection->method('offsetGet')->willReturn(
            $this->createStub(Adquisition::class)
        );
        $summary = $this->createStub(SummaryVO::class);
        $portfolio = new PortfolioDTO(
            $account,
            $outstandingPositionsCollection,
            $summary
        );
        $this->assertSame($account, $portfolio->getAccount());
        $this->assertSame($outstandingPositionsCollection, $portfolio->getCollection());
        $this->assertSame($summary, $portfolio->getSummary());
        $this->assertNotNull($portfolio->getPositionAdquisitionExpenses(0));
        $this->assertNotNull($portfolio->getPositionAdquisitionPrice(0));
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
        $outstandingPositionsCollection = new AdquisitionsCollection([]);
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
        $this->assertNull($portfolio->getPositionAdquisitionExpenses(0));        
    }
}
