<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Exchange\Exchange;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Stock\StockRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection;

/**
 * @covers xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 */
class StockTest extends TestCase
{
    private Currency&Stub $currency;
    private StockPriceVO&Stub $stockPrice;
    private StockRepositoryInterface&MockObject $repoStock;
    private AcquisitionRepositoryInterface&Stub $repoAcquisition;
    private EntityObjectRepositoryLoaderInterface&Stub $repoLoader;
    private Exchange&Stub $exchange;

    public function setUp(): void
    {
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->stockPrice = $this->createStub(StockPriceVO::class);
        $this->stockPrice->method('getCurrency')->willReturn($this->currency);
        $this->stockPrice->method('getValue')->willReturn('4.5614');
        $this->repoStock = $this->createMock(StockRepositoryInterface::class);
        $this->repoAcquisition = $this->createStub(AcquisitionRepositoryInterface::class);
        $this->repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap(
                [
                    [StockRepositoryInterface::class, $this->repoStock],
                    [AcquisitionRepositoryInterface::class, $this->repoAcquisition]
                ]
            )
        );
        $this->exchange = $this->createStub(Exchange::class);
    }

    public function testStockObjectIsCreated(): void
    {
        $this->currency->method('sameId')->willReturn(true);
        $name = 'ABCD Name';
        $stock = new Stock($this->repoLoader, 'ABCD', $name, $this->stockPrice, $this->exchange);
        $this->assertInstanceOf(Stock::class, $stock);
        $this->assertTrue($stock->sameId($stock));
        $this->assertSame($name, $stock->getName());
        $this->assertSame('4.5614', $stock->getPrice()->getValue());
        $this->assertSame($this->currency, $stock->getCurrency());
        $this->assertSame('ABCD', $stock->getId());
        $this->assertSame($this->exchange, $stock->getExchange());
    }

    public function testDuplicateStockCodeThrowsException(): void
    {
        $stock = new Stock($this->repoLoader, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);
        $this->repoStock->method('findById')->willReturn($stock);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('stockExists');
        new Stock($this->repoLoader, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);
    }

    /**
     * @dataProvider invalidCodes
     */
    public function testStockCodeFormat($code): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('stringLength');
        new Stock($this->repoLoader, $code, 'ABCD Name', $this->stockPrice, $this->exchange);
    }

    public static function invalidCodes(): array
    {
        return [
            [''], ['ABCDE']
        ];
    }

    /**
     * @dataProvider invalidNames
     */
    public function testStockNameFormat($name): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('stringLength');
        new Stock($this->repoLoader, 'ABCD', $name, $this->stockPrice, $this->exchange);
    }

    public static function invalidNames(): array
    {
        $name = '';
        for ($i=0; $i <256 ; $i++) { 
            $name .= mt_rand(0, 9);
        }
        return [
            [''], [$name]
        ];
    }
    
    public function testUpdateStockPriceWithInvalidCurrencyThrowsException(): void
    {
        $this->currency->method('sameId')->willReturn(false);
        $stock = new Stock($this->repoLoader, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('otherCurrencyExpected');
        $stock->setPrice($this->stockPrice);
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $stock = new Stock($this->repoLoader, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);
        $entity = new class implements EntityObjectInterface { public function sameId(EntityObjectInterface $otherEntity): bool { return true; }};
        $this->expectException(InvalidArgumentException::class);
        $stock->sameId($entity);
    }

    public function testSetPrice(): void
    {
        $this->currency->method('sameId')->willReturn(true);
        $stock = new Stock($this->repoLoader, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);
        /** @var StockPriceVO&Stub */
        $newStockPrice = $this->createStub(StockPriceVO::class);
        $newStockPrice->method('getValue')->willReturn('6.7824');
        $stock->setPrice($newStockPrice);
        $this->assertSame('6.7824', $stock->getPrice()->getValue());
    }

    public function testRemoveWhenHavingTransactionsWillThrowException(): void
    {
        /** @var AcquisitionsCollection&Stub */
        $acquisitionsColletion = $this->createStub(AcquisitionsCollection::class);
        $acquisitionsColletion->method('count')->willReturn(1);
        $this->repoAcquisition->method('findByStockId')->willReturn($acquisitionsColletion);
        /** @var Stock */
        $stock = $this->getMockBuilder(Stock::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('stockHasTransactions');
        $stock->persistRemove($this->repoLoader);
    }

    public function testRemove(): void
    {
        $this->repoStock->expects($this->once())->method('remove');
        /** @var AcquisitionsCollection&Stub */
        $acquisitionsColletion = $this->createStub(AcquisitionsCollection::class);
        $acquisitionsColletion->method('count')->willReturn(0);
        $this->repoAcquisition->method('findByStockId')->willReturn($acquisitionsColletion);
        /** @var Stock */
        $stock = $this->getMockBuilder(Stock::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $stock->persistRemove($this->repoLoader);
    }

    public function testUpdate(): void
    {
        $this->repoStock->expects($this->once())->method('persist');
        $this->repoStock->expects($this->once())->method('flush');
        /** @var AcquisitionsCollection&Stub */
        $acquisitionsColletion = $this->createStub(AcquisitionsCollection::class);
        $acquisitionsColletion->method('count')->willReturn(0);
        $this->repoAcquisition->method('findByStockId')->willReturn($acquisitionsColletion);
        /** @var Stock */
        $stock = $this->getMockBuilder(Stock::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $stock->persistUpdate($this->repoLoader);
    }

    public function testExceptionIsThrownOnCreateCommitFail(): void
    {
        $this->repoStock->expects($this->once())->method('persist')->willThrowException(new Exception());        
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        new Stock($this->repoLoader, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);
    }

    public function testExceptionIsThrownOnUpdateCommitFail(): void
    {
        /** @var Stock */
        $stock = $this->getMockBuilder(Stock::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $this->repoStock->expects($this->once())->method('persist')->willThrowException(new Exception());        
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        $stock->persistUpdate($this->repoLoader);
    }

    public function testExceptionIsThrownOnRemoveCommitFail(): void
    {
        /** @var Stock */
        $stock = $this->getMockBuilder(Stock::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $this->repoStock->expects($this->once())->method('remove')->willThrowException(new Exception());
        /** @var AcquisitionsCollection&Stub */    
        $acquisitionsColletion = $this->createStub(AcquisitionsCollection::class);
        $acquisitionsColletion->method('count')->willReturn(0);
        $this->repoAcquisition->method('findByStockId')->willReturn($acquisitionsColletion); 
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        $stock->persistRemove($this->repoLoader);
    }
}
