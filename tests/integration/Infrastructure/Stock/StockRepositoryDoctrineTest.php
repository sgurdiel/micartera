<?php declare(strict_types=1);

namespace Tests\integration\Infrastructure\Stock;

use Tests\integration\IntegrationTestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Exchange\Exchange;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Exchange\ExchangeRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\Exchange\Exchange
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\StocksCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Acquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Criteria\FiFoCriteria
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Exchange\ExchangeRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\AcquisitionRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine
 */
class StockRepositoryDoctrineTest extends IntegrationTestCase
{
    private StockRepositoryDoctrine $repoStock;
    private Currency $currencyEuro;
    private Currency $currencyDollar;
    private Exchange $exchange;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->repoStock = new StockRepositoryDoctrine(self::$registry);
        $repoCurrency = new CurrencyRepositoryDoctrine(self::$registry);
        $this->currencyEuro = $repoCurrency->findById('EUR');
        $this->currencyDollar = $repoCurrency->findById('USD');
        $repoExchange = new ExchangeRepositoryDoctrine(self::$registry);
        $this->exchange = $repoExchange->findById('MCE');
    }

    public function testTest(): void
    {
        $this->assertSame(true, true);
    }

    public function testStockIsAddedUpdatedAndRemoved(): void
    {
        $stock = new Stock($this->repoLoader, 'ABCD', 'ABCD Name', new StockPriceVO('2.6632', $this->currencyEuro), $this->exchange);
        $this->assertInstanceOf(Stock::class, $stock);
        parent::detachEntity($stock);
        $stock = $this->repoStock->findById($stock->getId());
        $this->assertInstanceOf(Stock::class, $stock);
        $newName = "ABCD Name New";
        $newPrice = new StockPriceVO('2.7400', $stock->getCurrency());
        $stock->setName($newName);
        $stock->setPrice($newPrice);
        $stock->persistUpdate($this->repoLoader);
        parent::detachEntity($stock);
        $stock = $this->repoStock->findById($stock->getId());
        $this->assertSame($newName, $stock->getName());
        $this->assertEquals($newPrice, $stock->getPrice());
        $stock->persistRemove($this->repoLoader);
        parent::detachEntity($stock);
        $this->assertSame(null, $this->repoStock->findById($stock->getId()));
    }

    public function testfindByIdOrThrowException(): void
    {
        $stockCode = 'CABK';
        $stock = $this->repoStock->findByIdOrThrowException($stockCode);
        $this->assertInstanceOf(Stock::class, $stock);
        $this->assertSame($stockCode, $stock->getId());
    }

    public function testfindByIdOrThrowExceptionWithNonExistingThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('expectedPersistedObjectNotFound');
        $this->repoStock->findByIdOrThrowException('UYY');
    }

    public function testFindByCurrency(): void
    {
        parent::$loadFixtures = true;
        $expected = [
            new Stock($this->repoLoader, 'EFGH', 'EFGGH Name', new StockPriceVO('4.2300', $this->currencyDollar), $this->exchange),
            new Stock($this->repoLoader, 'IJKL', 'IJKL Name', new StockPriceVO('5.2300', $this->currencyDollar), $this->exchange),
            new Stock($this->repoLoader, 'MNOP', 'MNOP Name', new StockPriceVO('4.2301', $this->currencyDollar), $this->exchange),
        ];
        $amount = count($expected);
        $stocks = $this->repoStock->findByCurrency($this->currencyDollar, 10, 0, 'code', 'ASC');
        $this->assertCount($amount, $stocks);
        $ordering = [0 => 0, 1 => 1, 2 => 2];
        foreach ($stocks as $key => $stock) {
            $this->assertTrue($stock->sameId($expected[$ordering[$key]]));
        }
        $stocks = $this->repoStock->findByCurrency($this->currencyDollar, 10, 0, 'code', 'DESC');
        $this->assertCount($amount, $stocks);
        $ordering = [0 => 2, 1 => 1, 2 => 0];
        foreach ($stocks as $key => $stock) {
            $this->assertTrue($stock->sameId($expected[$ordering[$key]]));
        }
        $stocks = $this->repoStock->findByCurrency($this->currencyDollar, 10, 0, 'price', 'ASC');
        $this->assertCount($amount, $stocks);
        $ordering = [0 => 0, 1 => 2, 2 => 1];
        foreach ($stocks as $key => $stock) {
            $this->assertTrue($stock->sameId($expected[$ordering[$key]]));
        }
        $stocks = $this->repoStock->findByCurrency($this->currencyDollar, 10, 0, 'price', 'DESC');
        $this->assertCount($amount, $stocks);
        $ordering = [0 => 1, 1 => 2, 2 => 0];
        foreach ($stocks as $key => $stock) {
            $this->assertTrue($stock->sameId($expected[$ordering[$key]]));
        }
    }

    public function testRemovingStockHavingTransactionsThrowsException(): void
    {
        $stock = $this->repoStock->findById('CABK');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('stockHasTransactions');
        $stock->persistRemove($this->repoLoader);
    }
}
