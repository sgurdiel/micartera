<?php declare(strict_types=1);

namespace Tests\unit\Application\Query\Currency;

use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\Bundle\DomainBundle\Application\Query\EntityObjectsCollectionQueryResponse;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Application\Query\Currency\CurrencyQuery;
use xVer\MiCartera\Domain\Currency\CurrenciesCollection;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine;
use xVer\MiCartera\Domain\Currency\CurrencyRepositoryInterface;

/**
 * @covers xVer\MiCartera\Application\Query\Currency\CurrencyQuery
 */
class CurrencyQueryTest extends KernelTestCase
{    
    public function testByIdentifierQuerySucceeds(): void
    {
        $repoCurrency = $this->createStub(CurrencyRepositoryDoctrine::class);
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $repoLoader->method('load')->will(
            $this->returnValueMap([
                [CurrencyRepositoryInterface::class, $repoCurrency]
            ])
        );
        $query = new CurrencyQuery($repoLoader);
        $response = $query->all();
        $this->assertInstanceOf(EntityObjectsCollectionQueryResponse::class, $response);
        $this->assertInstanceOf(CurrenciesCollection::class, $response->getCollection());
    }
}
