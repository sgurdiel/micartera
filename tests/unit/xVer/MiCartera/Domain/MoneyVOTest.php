<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Domain;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;

/**
 * @covers xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 */
class MoneyVOTest extends TestCase
{
    private static Currency $currency;

    public static function setUpBeforeClass(): void
    {
        self::$currency = new Currency('EUR', '€', 2);
    }

    public function testCanInstentiate(): void
    {
        $instance = MoneyVO::instantiate('1.33', self::$currency);
        $this->assertInstanceOf(MoneyVO::class, $instance);
    }

    public function testValueFormat(): void
    {
        $testPrices = ['0.123','1,1','100,000'];
        $exceptionsExpected = count($testPrices);
        $exceptionsThrown = 0;
        $exceptionsMessagesCorrect = 0;
        foreach ($testPrices as $testPrice) {
            try {
                $aux = new MoneyVO($testPrice, self::$currency);
                unset($aux);
            } catch (DomainException $th) {
                $exceptionsThrown++;
                if ($th->getMessage() === 'moneyFormat') {
                    $exceptionsMessagesCorrect++;
                }
            }
        }
        $this->assertSame($exceptionsExpected, $exceptionsThrown);
        $this->assertSame($exceptionsExpected, $exceptionsMessagesCorrect);

        $a = new MoneyVO('5.0', self::$currency);
        $this->assertSame('5.00', $a->getValue());
    }

    public function testAdd(): void
    {
        $a = new MoneyVO('4.55', self::$currency);
        $b = new MoneyVO('1.01', self::$currency);
        $c = new MoneyVO('45.33', self::$currency);
        $d = new MoneyVO('0.45', self::$currency);
        $e = new MoneyVO('3', self::$currency);
        $f = new MoneyVO('1', self::$currency);
        $this->assertSame('5.56', $a->add($b)->getValue());
        $this->assertSame('49.88', $a->add($c)->getValue());
        $this->assertSame('5.00', $a->add($d)->getValue());
        $this->assertSame('4.00', $e->add($f)->getValue());
    }

    public function testAddWithDifferentCurrencyThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('moneyOperationRequiresBothOperandsSameCurrency');
        $a = new MoneyVO('4.55', self::$currency);
        $auxCurrency = new Currency('USD', '$', 2);
        $b = new MoneyVO('1.01', $auxCurrency);
        $a->add($b);
    }

    public function testSubtract(): void
    {
        $a = new MoneyVO('5.55', self::$currency);
        $b = new MoneyVO('1.01', self::$currency);
        $c = new MoneyVO('10.05', self::$currency);
        $d = new MoneyVO('0.55', self::$currency);
        $e = new MoneyVO('4', self::$currency);
        $f = new MoneyVO('1', self::$currency);
        $this->assertSame('4.54', $a->subtract($b)->getValue());
        $this->assertSame('-4.50', $a->subtract($c)->getValue());
        $this->assertSame('5.00', $a->subtract($d)->getValue());
        $this->assertSame('3.00', $e->subtract($f)->getValue());
    }

    public function testSubtractWithDifferentCurrencyThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('moneyOperationRequiresBothOperandsSameCurrency');
        $a = new MoneyVO('4.55', self::$currency);
        $auxCurrency = new Currency('USD', '$', 2);
        $b = new MoneyVO('1.01', $auxCurrency);
        $a->subtract($b);
    }

    public function testPercent(): void
    {
        $a = new MoneyVO('5.55', self::$currency);
        $b = new MoneyVO('5.57', self::$currency);
        $c = new MoneyVO('10.05', self::$currency);
        $d = new MoneyVO('14.55', self::$currency);
        $e = new MoneyVO('5', self::$currency);
        $f = new MoneyVO('10', self::$currency);
        $g = new MoneyVO('10', self::$currency);
        $h = new MoneyVO('0.00', self::$currency);
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
        $a = new MoneyVO('4.55', self::$currency);
        $auxCurrency = new Currency('USD', '$', 2);
        $b = new MoneyVO('1.01', $auxCurrency);
        $a->percentageDifference($b);
    }

    public function testSame(): void
    {
        $a = new MoneyVO('5.55', self::$currency);
        $b = new MoneyVO('5.55', self::$currency);
        $this->assertTrue($a->same($b));
    }

    public function testSameWithDifferentCurrencyThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('moneyOperationRequiresBothOperandsSameCurrency');
        $a = new MoneyVO('4.55', self::$currency);
        $auxCurrency = new Currency('USD', '$', 2);
        $b = new MoneyVO('1.01', $auxCurrency);
        $a->same($b);
    }

    public function testMultiply(): void
    {
        $price = new MoneyVO('10.10', self::$currency);
        $price = $price->multiply('10');
        $this->assertSame('101.00', $price->getValue());
    }
}
