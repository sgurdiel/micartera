<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Domain\Stock;

use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;

/**
 * @covers xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
  */
class StockPriceVOTest extends TestCase
{
    private static Currency $currency;

    public static function setUpBeforeClass(): void
    {
        self::$currency = new Currency('EUR', '€', 2);
    }

    public function testCanInstentiate(): void
    {
        $instance = StockPriceVO::instantiate('1.33', self::$currency);
        $this->assertInstanceOf(StockPriceVO::class, $instance);
    }

    public function testValueFormat(): void
    {
        $aux = new StockPriceVO('5.1234', self::$currency);
        $this->assertSame('5.1234', $aux->getValue());
        $this->assertSame(self::$currency, $aux->getCurrency());
    }

    public function testInvalidValueFormatThrowsException(): void
    {
        $testPrices = ["0.123456","1,1","-1.5","100000"];
        $exceptionsExpected = count($testPrices);
        $exceptionsThrown = 0;
        foreach ($testPrices as $testPrice) {
            try {
                $aux = new StockPriceVO($testPrice, self::$currency);
                unset($aux);
            } catch (DomainException $th) {
                $exceptionsThrown++;
            }
        }
        $this->assertSame($exceptionsExpected, $exceptionsThrown);
    }

    public function testMultiply(): void
    {
        $price = new StockPriceVO('643.4566', self::$currency);
        $priceTotal = $price->multiply('100');
        $this->assertInstanceOf(StockPriceVO::class, $priceTotal);
        $this->assertSame('64345.6600', $priceTotal->getValue());
        $this->assertSame(self::$currency, $priceTotal->getCurrency());
    }

    public function testToMoney(): void
    {
        $price = new StockPriceVO('643.4566', self::$currency);
        $money = $price->toMoney();
        $this->assertInstanceOf(MoneyVO::class, $money);
        $this->assertSame('643.46', $money->getValue());
        $this->assertSame(self::$currency, $money->getCurrency());
    }
}
