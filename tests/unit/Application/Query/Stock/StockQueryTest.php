<?php declare(strict_types=1);

namespace Tests\unit\Application\Query\Stock;

use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\Bundle\DomainBundle\Application\Query\EntityObjectsCollectionQueryResponse;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Application\Query\Stock\StockQuery;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StocksCollection;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\MiCartera\Domain\Stock\StockRepositoryInterface;

/**
 * @covers xVer\MiCartera\Application\Query\Stock\StockQuery
 */
class StockQueryTest extends KernelTestCase
{    
    public function testByAccountsCurrencyQuerySucceeds(): void
    {
        $repoStock = $this->createStub(StockRepositoryDoctrine::class);
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [StockRepositoryInterface::class, $repoStock],
                [AccountRepositoryInterface::class, $repoAccount]
            ])
        );
        $query = new StockQuery($repoLoader);
        $response = $query->byAccountsCurrency(
            '',
            0,
            0
        );
        $this->assertInstanceOf(EntityObjectsCollectionQueryResponse::class, $response);
        $this->assertInstanceOf(StocksCollection::class, $response->getCollection());

        $query = new StockQuery($repoLoader);
        $response = $query->byAccountsCurrency(
            '',
            10,
            0
        );
        $this->assertInstanceOf(EntityObjectsCollectionQueryResponse::class, $response);
        $this->assertInstanceOf(StocksCollection::class, $response->getCollection());
    }

    public function testByCodeQuerySucceeds(): void
    {
        $repoStock = $this->createStub(StockRepositoryDoctrine::class);
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [StockRepositoryInterface::class, $repoStock],
            ])
        );
        $query = new StockQuery($repoLoader);
        $response = $query->byCode('');
        $this->assertInstanceOf(Stock::class, $response);
    }
}
