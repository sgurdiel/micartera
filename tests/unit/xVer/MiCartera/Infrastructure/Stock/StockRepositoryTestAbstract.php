<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\Stock;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryInterface;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

abstract class StockRepositoryTestAbstract extends KernelTestCase implements StockRepositoryTestInterface
{
    protected TransactionRepositoryInterface $repoTrans;
    protected string $code;
    protected string $code2;
    protected string $name;
    protected Currency $currency;
    protected Currency $currency2;
    protected StockPriceVO $price;
    protected Stock $stock;
    /** @var Stock[] */
    protected array $tearDownStocks;

    public function testStockIsAddedUpdatedAndRemoved(): void
    {
        $stock = $this->repo->add($this->stock);
        $this->assertInstanceOf(Stock::class, $stock);
        $auxStock = $this->repo->findById($stock->getId());
        $this->assertInstanceOf(Stock::class, $auxStock);
        $this->assertTrue($stock->sameId($auxStock));
        $this->assertSame($stock->getName(), $auxStock->getName());
        $this->assertTrue($stock->getCurrency()->sameId($auxStock->getCurrency()));
        $this->assertSame($stock->getPrice()->getValue(), $auxStock->getPrice()->getValue());
        $newName = "ABCD Name New";
        $newPrice = new StockPriceVO('2.7400', $stock->getCurrency());
        $stock->setName($newName);
        $stock->setPrice($newPrice);
        $this->repo->update($stock);
        $stock = $this->repo->findById($stock->getId());
        $this->assertSame($newName, $stock->getName());
        $this->assertEquals($newPrice, $stock->getPrice());
        $this->repo->remove($stock, $this->repoTrans);
        $stock = $this->repo->findById($stock->getId());
        $this->assertSame(null, $stock);
    }

    public function testfindByIdOrThrowExceptionWithNonExistingThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('expectedPersistedObjectNotFound');
        $this->repo->findByIdOrThrowException('UYY');
    }

    public function testFindByCurrency(): void
    {
        $expected = [
            new Stock('EFGH', 'EFGGH Name', new StockPriceVO('4.2300', $this->currency2)),
            new Stock('IJKL', 'IJKL Name', new StockPriceVO('5.2300', $this->currency2)),
            new Stock('MNOP', 'MNOP Name', new StockPriceVO('4.2301', $this->currency2))
        ];
        foreach ($expected as $stock) {
            $this->repo->add($stock);
            $this->tearDownStocks[] = $stock;
        }
        $amount = count($expected);
        $stocks = $this->repo->findByCurrencySorted($this->currency2, 10, 0, 'code', 'ASC');
        $this->assertCount($amount, $stocks);
        $ordering = [0 => 0, 1 => 1, 2 => 2];
        foreach ($stocks as $key => $stock) {
            $this->assertTrue($stock->sameId($expected[$ordering[$key]]));
        }
        $stocks = $this->repo->findByCurrencySorted($this->currency2, 10, 0, 'code', 'DESC');
        $this->assertCount($amount, $stocks);
        $ordering = [0 => 2, 1 => 1, 2 => 0];
        foreach ($stocks as $key => $stock) {
            $this->assertTrue($stock->sameId($expected[$ordering[$key]]));
        }
        $stocks = $this->repo->findByCurrencySorted($this->currency2, 10, 0, 'price', 'ASC');
        $this->assertCount($amount, $stocks);
        $ordering = [0 => 0, 1 => 2, 2 => 1];
        foreach ($stocks as $key => $stock) {
            $this->assertTrue($stock->sameId($expected[$ordering[$key]]));
        }
        $stocks = $this->repo->findByCurrencySorted($this->currency2, 10, 0, 'price', 'DESC');
        $this->assertCount($amount, $stocks);
        $ordering = [0 => 1, 1 => 2, 2 => 0];
        foreach ($stocks as $key => $stock) {
            $this->assertTrue($stock->sameId($expected[$ordering[$key]]));
        }
    }

    public function testRemovingStockHavingTransactionsThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('stockHasTransactions');
        $stock = $this->repo->findById($this->code2);
        $this->repo->remove($stock, $this->repoTrans);
    }

    public function testAddingStockWithExistingCodeThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('stockExists');
        $stock = $this->repo->add($this->stock);
        $this->tearDownStocks[] = $stock;
        $stock = $this->repo->findById($this->code);
        $stock = $this->repo->add($stock);
    }
}