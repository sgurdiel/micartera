<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Domain\Transaction;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Transaction\PortfolioDTO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;

/**
 * @covers xVer\MiCartera\Domain\Transaction\PortfolioDTO
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 */
class PortfolioDTOTest extends TestCase
{
    private static Account $account;
    private static Currency $currency;
    private static Stock $stock;
    /** @var Transaction[] */
    private static array $outstandingPositions = [];
    private static  \DateTimeZone $timezone;

    public static function setUpBeforeClass(): void
    {
        self::$timezone = new \DateTimeZone('Europe/Madrid');
        self::$currency = new Currency('EUR', '€', 2);
        $price = new StockPriceVO('4.5600', self::$currency);
        self::$stock = new Stock('ABCD', 'ABCD Name', $price);
        $dateTimeUtc = new \DateTime('yesterday', self::$timezone);
        $expenses = new MoneyVO('3.34', self::$currency);
        self::$account = new Account('test@example.com', 'password', self::$currency, self::$timezone, ['ROLE_USER']);
        self::$outstandingPositions[] = new Transaction(Transaction::TYPE_BUY, self::$stock, $dateTimeUtc, 100, $expenses, self::$account);
        $price = new StockPriceVO('4.9600', self::$currency);
        self::$stock->setPrice($price);
        $dateTimeUtc2 = clone $dateTimeUtc;
        $expenses = new MoneyVO('3.64', self::$currency);
        self::$outstandingPositions[] = new Transaction(Transaction::TYPE_BUY, self::$stock, $dateTimeUtc2->add(new \DateInterval("PT30S")), 400, $expenses, self::$account);
    }

    public function testPortfolioDTO(): void
    {
        $price = new StockPriceVO('6.5600', self::$currency);
        self::$stock->setPrice($price);
        $portfolio = new PortfolioDTO(self::$account, self::$outstandingPositions);
        $this->assertSame(self::$account, $portfolio->getAccount());
        $this->assertIsArray($portfolio->getOutstandingPositions());
        $this->assertCount(2, $portfolio->getOutstandingPositions());
        foreach($portfolio->getOutstandingPositions() as $key => $outstandingPosition){
            $this->assertInstanceOf(Transaction::class, $outstandingPosition);
            $this->assertSame(self::$outstandingPositions[$key]->getStock(), $outstandingPosition->getStock());
            $this->assertSame('6.5600', $outstandingPosition->getStock()->getPrice()->getValue());
            $this->assertSame(self::$outstandingPositions[$key]->getAmountOutstanding(), $outstandingPosition->getAmountOutstanding());
            $purchasePrice = self::$outstandingPositions[$key]->getPrice()->multiply((string) self::$outstandingPositions[$key]->getAmountOutstanding());
            $this->assertEquals($purchasePrice, $portfolio->getTransOutstandingPurchasePrice($key));
            $currentPrice = self::$outstandingPositions[$key]->getStock()->getPrice()->multiply((string) self::$outstandingPositions[$key]->getAmountOutstanding());
            $this->assertEquals($currentPrice, $portfolio->getTransOutstandingCurrentPrice($key));
            $this->assertEquals($currentPrice->toMoney()->subtract($purchasePrice->toMoney()), $portfolio->getTransOutstandingProfitPrice($key));
            $this->assertEquals($purchasePrice->toMoney()->percentageDifference($currentPrice->toMoney()), $portfolio->getTransOutstandingProfitPercentage($key));
        }
        $this->assertSame('2440.00', $portfolio->getPurchasePrice()->getValue());
        $this->assertSame('3280.00', $portfolio->getCurrentPrice()->getValue());
        $this->assertSame('840.00', $portfolio->getProfitForecastPrice()->getValue());
        $this->assertSame('34.43', $portfolio->getProfitForecastPercentage());
        
    }

    public function testInvalidOutstandingPositionsArgumentThrowsExeption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $invalidOutstandingPositions = [1, 2 , 3];
        $aux = new PortfolioDTO(self::$account, $invalidOutstandingPositions);
        unset($aux);
    }

    public function testSellTransactionAsOutstandingPositionThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $dateTimeUtc = new \DateTime('now', self::$timezone);
        $expenses = new MoneyVO('5.23', self::$currency);
        $returnOutstandingPositions = [];
        $returnOutstandingPositions[] = new Transaction(Transaction::TYPE_SELL, self::$stock, $dateTimeUtc, 10, $expenses, self::$account);
        $aux = new PortfolioDTO(self::$account, $returnOutstandingPositions);
        unset($aux);
    }
}
