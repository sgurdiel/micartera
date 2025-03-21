<?php declare(strict_types=1);

namespace Tests\unit\Application\Query\Stock\Portfolio;

use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Application\Query\Stock\Portfolio\PortfolioQuery;
use xVer\MiCartera\Application\Query\Stock\Portfolio\PortfolioDTO;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Portfolio\SummaryVO;
use xVer\MiCartera\Domain\Stock\StockRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionRepositoryInterface;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Application\Query\Stock\Portfolio\PortfolioQuery
 * @uses xVer\MiCartera\Application\Query\Stock\Portfolio\PortfolioDTO
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 */
class PortfolioQueryTest extends KernelTestCase
{   
    public function testQueryCommandSucceeds(): void
    {
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        /** @var AcquisitionRepositoryInterface&Stub */
        $repoTransaction = $this->createStub(AcquisitionRepositoryInterface::class);        
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [AccountRepositoryInterface::class, $repoAccount],
                [AcquisitionRepositoryInterface::class, $repoTransaction]
            ])
        );
        $query = new PortfolioQuery($repoLoader);
        $portfolioDTO = $query->getPortfolio('test@example.com');
        $this->assertInstanceOf(PortfolioDTO::class, $portfolioDTO);
    }

    public function testGetStockPortfolioSummary(): void
    {
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        $repoStock = $this->createStub(StockRepositoryDoctrine::class);
        /** @var AcquisitionRepositoryInterface&Stub */
        $repoTransaction = $this->createStub(AcquisitionRepositoryInterface::class);        
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [AccountRepositoryInterface::class, $repoAccount],
                [StockRepositoryInterface::class, $repoStock],
                [AcquisitionRepositoryInterface::class, $repoTransaction]
            ])
        );
        $query = new PortfolioQuery($repoLoader);
        $summaryVO = $query->getStockPortfolioSummary('test@example.com', 'TEST');
        $this->assertInstanceOf(SummaryVO::class, $summaryVO);
    }
}
