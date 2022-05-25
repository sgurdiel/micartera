<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Application\Query;

use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Application\Query\QueryResponse;
use xVer\MiCartera\Application\Query\StockQuery;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Application\Query\StockQuery
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses xVer\Bundle\DomainBundle\Application\Query\QueryResponse
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryInMemory
 */
class StockQueryTest extends TestCase
{
    public function testQueryCommandSucceeds(): void
    {
        $repo = new StockRepositoryInMemory();
        $currency = new Currency('EUR', '€', 2);
        $price = new StockPriceVO('2.66', $currency);
        $stock = new Stock('TEF', "Telefonica", $price);
        $stock = $repo->add($stock);
        $price2 = new StockPriceVO('4.164', $currency);
        $stock2 = new Stock('CABK', "Caixabank", $price2);
        $stock2 = $repo->add($stock2);
        $command = new StockQuery();
        $queryResponse = $command->execute($repo, $currency, 10, 0, 'code', 'ASC');
        $this->assertInstanceOf(QueryResponse::class, $queryResponse);
        $this->assertIsArray($queryResponse->getObjects());
        $this->assertCount(2, $queryResponse->getObjects());
        $this->assertInstanceOf(Stock::class, $queryResponse->getObjects()[0]);
        $this->assertIsBool($queryResponse->getNextPage());
        $this->assertIsBool($queryResponse->getPrevPage());
        $this->assertIsInt($queryResponse->getPage());
        $currency2 = new Currency('USD', '$', 2);
        $queryResponse = $command->execute($repo, $currency2, 10, 0, 'code', 'ASC');
        $this->assertInstanceOf(QueryResponse::class, $queryResponse);
        $this->assertIsArray($queryResponse->getObjects());
        $this->assertCount(0, $queryResponse->getObjects());
        $this->assertIsBool($queryResponse->getNextPage());
        $this->assertIsBool($queryResponse->getPrevPage());
        $this->assertIsInt($queryResponse->getPage());
    }
}
