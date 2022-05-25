<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Domain\Stock;

use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;

/**
 * @covers xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 */
class StockTest extends TestCase
{
    private static Currency $currency;

    public static function setUpBeforeClass(): void
    {
        self::$currency = new Currency('EUR', '€', 2);
    }

    public function testStockObjectIsCreated(): void
    {
        $name = 'ABCD Name';
        $price = new StockPriceVO('4.5600', self::$currency);
        $stock = new Stock('ABCD', $name, $price);
        $this->assertInstanceOf(Stock::class, $stock);
        $this->assertTrue($stock->sameId($stock));
        $this->assertSame($name, $stock->getName());
        $this->assertSame('4.5600', $stock->getPrice()->getValue());
        $this->assertSame('EUR', $stock->getCurrency()->getISO3());
        $this->assertTrue($stock->sameId($stock));
    }

    public function testStockCodeFormat(): void
    {
        $testStrings = ["","ABCDE"];
        $exceptionsExpected = count($testStrings);
        $exceptionsThrown = 0;
        $name = 'ABCD Name';
        $price = new StockPriceVO('4.56', self::$currency);
        foreach ($testStrings as $testString) {
            try {
                $aux = new Stock($testString, $name, $price);
                unset($aux);
            } catch (DomainException $th) {
                $exceptionsThrown++;
            }
        }
        $this->assertSame($exceptionsExpected, $exceptionsThrown);
    }

    public function testStockNameFormat(): void
    {
        $exceptionsExpected = 2;
        $exceptionsThrown = 0;
        $price = new StockPriceVO('4.5600', self::$currency);
        try {
            $aux = new Stock('ABCD', '', $price);
            unset($aux);
        } catch (DomainException $th) {
            $exceptionsThrown++;
        }
        $name = '';
        for ($i=0; $i <256 ; $i++) { 
            $name .= mt_rand(0, 9);
        }
        try {
            $aux = new Stock('ABCD', $name, $price);
            unset($aux);
        } catch (DomainException $th) {
            $exceptionsThrown++;
        }
        $this->assertSame($exceptionsExpected, $exceptionsThrown);
    }
    
    public function testUpdateStockPriceWithInvalidCurrencyThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('otherCurrencyExpected');
        $name = 'ABCD Name';
        $price = new StockPriceVO('4.5600', self::$currency);
        $stock = new Stock('ABCD', $name, $price);
        $badCurrency = new Currency('USD', "$", 2);
        $newPrice = new StockPriceVO('5.4000', $badCurrency);
        $stock->setPrice($newPrice);
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $name = 'ABCD Name';
        $price = new StockPriceVO('4.5600', self::$currency);
        $stock = new Stock('ABCD', $name, $price);
        $account = new Account("test@example.com", "password", self::$currency, new \DateTimeZone('UTC'), ['ROLE_USER']);
        $stock->sameId($account);
    }
}
