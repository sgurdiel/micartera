<?php declare(strict_types=1);

namespace Tests\unit\Application\Query\Portfolio;

use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Application\Query\Portfolio\PortfolioQuery;
use xVer\MiCartera\Application\Query\Portfolio\PortfolioDTO;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionRepositoryInterface;

/**
 * @covers xVer\MiCartera\Application\Query\Portfolio\PortfolioQuery
 * @uses xVer\MiCartera\Application\Query\Portfolio\PortfolioDTO
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 */
class PortfolioQueryTest extends KernelTestCase
{   
    public function testQueryCommandSucceeds(): void
    {
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        /** @var AdquisitionRepositoryInterface&Stub */
        $repoTransaction = $this->createStub(AdquisitionRepositoryInterface::class);        
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [AccountRepositoryInterface::class, $repoAccount],
                [AdquisitionRepositoryInterface::class, $repoTransaction]
            ])
        );
        $query = new PortfolioQuery($repoLoader);
        $portfolioDTO = $query->getPortfolio('test@example.com');
        $this->assertInstanceOf(PortfolioDTO::class, $portfolioDTO);
    }
}
