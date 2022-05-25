<?php declare(strict_types=1);

namespace Tests\unit\Domain;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\MoneyVO;

/**
 * @covers xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\NumberOperation
 */
class MoneyVOTest extends TestCase
{
    private Currency $currency;
    private Currency $currency2;

    public function setUp(): void
    {
        /** @var Currency&Stub */
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->currency->method('getIso3')->willReturn('EUR');

        /** @var Currency&Stub */
        $this->currency2 = $this->createStub(Currency::class);
        $this->currency2->method('getIso3')->willReturn('USD');
    }

    public function testAdd(): void
    {
        $a = new MoneyVO('4.55', $this->currency);
        $b = new MoneyVO('1.01', $this->currency);
        $c = new MoneyVO('45.33', $this->currency);
        $d = new MoneyVO('0.45', $this->currency);
        $e = new MoneyVO('3', $this->currency);
        $f = new MoneyVO('1', $this->currency);
        $this->assertSame('5.56', $a->add($b)->getValue());
        $this->assertSame('49.88', $a->add($c)->getValue());
        $this->assertSame('5.00', $a->add($d)->getValue());
        $this->assertSame('4.00', $e->add($f)->getValue());
    }

    public function testAddWithDifferentCurrencyThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('moneyOperationRequiresBothOperandsSameCurrency');
        $a = new MoneyVO('4.55', $this->currency);
        $b = new MoneyVO('1.01', $this->currency2);
        $a->add($b);
    }

    public function testSubtract(): void
    {
        $a = new MoneyVO('5.55', $this->currency);
        $b = new MoneyVO('1.01', $this->currency);
        $c = new MoneyVO('10.05', $this->currency);
        $d = new MoneyVO('0.55', $this->currency);
        $e = new MoneyVO('4', $this->currency);
        $f = new MoneyVO('1', $this->currency);
        $this->assertSame('4.54', $a->subtract($b)->getValue());
        $this->assertSame('-4.50', $a->subtract($c)->getValue());
        $this->assertSame('5.00', $a->subtract($d)->getValue());
        $this->assertSame('3.00', $e->subtract($f)->getValue());
    }

    public function testSubtractWithDifferentCurrencyThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('moneyOperationRequiresBothOperandsSameCurrency');
        $a = new MoneyVO('4.55', $this->currency);
        $b = new MoneyVO('1.01', $this->currency2);
        $a->subtract($b);
    }

    public function testPercent(): void
    {
        $a = new MoneyVO('5.55', $this->currency);
        $b = new MoneyVO('5.57', $this->currency);
        $c = new MoneyVO('10.05', $this->currency);
        $d = new MoneyVO('14.55', $this->currency);
        $e = new MoneyVO('5', $this->currency);
        $f = new MoneyVO('10', $this->currency);
        $g = new MoneyVO('10', $this->currency);
        $h = new MoneyVO('0.00', $this->currency);
        $this->assertSame('0.36', $a->percentageDifference($b));
        $this->assertSame('81.08', $a->percentageDifference($c));
        $this->assertSame('162.16', $a->percentageDifference($d));
        $this->assertSame('100.00', $e->percentageDifference($f));
        $this->assertSame('0.00', $f->percentageDifference($g));
        $this->assertSame('100.00', $h->percentageDifference($f));
        $this->assertSame('-100.00', $e->percentageDifference($h));
    }

    public function testPercentWithDifferentCurrencyThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('moneyOperationRequiresBothOperandsSameCurrency');
        $a = new MoneyVO('4.55', $this->currency);
        $b = new MoneyVO('1.01', $this->currency2);
        $a->percentageDifference($b);
    }

    public function testSame(): void
    {
        $a = new MoneyVO('5.55', $this->currency);
        $b = new MoneyVO('5.55', $this->currency);
        $this->assertTrue($a->same($b));
    }

    public function testSameWithDifferentCurrencyThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('moneyOperationRequiresBothOperandsSameCurrency');
        $a = new MoneyVO('4.55', $this->currency);
        $b = new MoneyVO('1.01', $this->currency2);
        $a->same($b);
    }

    public function testMultiply(): void
    {
        $price = new MoneyVO('10.10', $this->currency);
        $price = $price->multiply('10');
        $this->assertSame('101.00', $price->getValue());
    }

    public function testDivide(): void
    {
        $price = new MoneyVO('10.10', $this->currency);
        $price2 = new MoneyVO('30.00', $this->currency);
        $this->assertSame('1.01', $price->divide('10')->getValue());
        $this->assertSame('0.17', $price->divide('59')->getValue());
        $this->assertSame('3.00', $price2->divide('10')->getValue());
        $this->assertSame('0.50', $price2->divide('59')->getValue());
    }
}
