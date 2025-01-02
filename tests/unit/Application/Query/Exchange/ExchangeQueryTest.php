<?php declare(strict_types=1);

namespace Tests\unit\Application\Query\Exchange;

use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\Bundle\DomainBundle\Application\Query\EntityObjectsCollectionQueryResponse;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Application\Query\Exchange\ExchangeQuery;
use xVer\MiCartera\Domain\Exchange\ExchangesCollection;
use xVer\MiCartera\Infrastructure\Exchange\ExchangeRepositoryDoctrine;
use xVer\MiCartera\Domain\Exchange\ExchangeRepositoryInterface;

/**
 * @covers xVer\MiCartera\Application\Query\Exchange\ExchangeQuery
 */
class ExchangeQueryTest extends KernelTestCase
{    
    public function testByIdentifierQuerySucceeds(): void
    {
        $repoExchange = $this->createStub(ExchangeRepositoryDoctrine::class);
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [ExchangeRepositoryInterface::class, $repoExchange]
            ])
        );
        $query = new ExchangeQuery($repoLoader);
        $response = $query->all();
        $this->assertInstanceOf(EntityObjectsCollectionQueryResponse::class, $response);
        $this->assertInstanceOf(ExchangesCollection::class, $response->getCollection());
    }
}
