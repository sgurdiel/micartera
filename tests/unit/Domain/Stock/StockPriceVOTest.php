<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Number\Number;
use xVer\MiCartera\Domain\Stock\StockPriceVO;

/**
 * @covers xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 * @uses xVer\MiCartera\Domain\MoneyVO
  */
class StockPriceVOTest extends TestCase
{
    private Currency&Stub $currency;

    public function setUp(): void
    {
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('getDecimals')->willReturn(2);
    }

    public function testCanInstentiate(): void
    {
        $instance = new StockPriceVO('1.33', $this->currency);
        $this->assertInstanceOf(StockPriceVO::class, $instance);
    }

    public function testMultiply(): void
    {
        $price = new StockPriceVO('643.4566', $this->currency);
        $priceTotal = $price->multiply(new Number('100'));
        $this->assertInstanceOf(StockPriceVO::class, $priceTotal);
        $this->assertSame('64345.66', $priceTotal->getValue());
        $this->assertSame($this->currency, $priceTotal->getCurrency());
    }

    /**
     * @dataProvider valuesToTest
     */
    public function testValidValues(string $testPrice, string $expected, bool $exception): void
    {
        if ($exception) {
            $this->expectException(DomainException::class);
            $this->expectExceptionMessage('enterNumberBetween');
        }
        $price = new StockPriceVO($testPrice, $this->currency);
        if (false === $exception) {
            $this->assertSame($expected, $price->getValue());
        }
    }

    public static function valuesToTest(): array
    {
        return [
            ['0.1234', '0.1234', false],
            ['99999.9999', '99999.9999', false],
            ['0.12', '0.12', false],
            ['0.1200', '0.12', false],
            ['1', '1', false],
            ['-1', '', true],
            ['1000000', '', true],
        ];
    }

    /** @dataProvider moneyValues */
    public function testToMoney(): void
    {
        $price = new StockPriceVO('643.4566', $this->currency);
        $money = $price->toMoney();
        $this->assertInstanceOf(MoneyVO::class, $money);
        $this->assertSame('643.45', $money->getValue());
        $this->assertSame($this->currency, $money->getCurrency());
    }

    public static function moneyValues(): array
    {
        return [
            ['1544.2', '1544.20'],
            ['643.4511', '643.45'],
            ['0.0099', '0.00'],
            ['-1.0099', '-1.00'],
        ];
    }
}
