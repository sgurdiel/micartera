<?php declare(strict_types=1);

namespace Tests\unit\Domain\Number;

use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Number\Number;

/**
 * @covers xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 */
class NumberTest extends TestCase
{

    /** @dataProvider formatsToTest */
    public function testValidateFormat(string $value, bool $exception): void
    {        
        if ($exception) {
            $this->expectException(DomainException::class);
            $this->expectExceptionMessage('numberFormat');
        } else {
            $this->expectNotToPerformAssertions();
        }
        new Number($value, true, $value, $value);
    }

    public static function formatsToTest(): array
    {
        return [
            ['-1', false],
            ['0', false],
            ['1', false],
            ['-1.001', false],
            ['0.001', false],
            ['1.001', false],
            ['a', true],
            ['1.', true],
            ['.1', true],
            ['1e2', true],
            ['1a', true],
            ['', true],
        ];
    }

    /** @dataProvider maxDecimalsToTest */
    public function testMaxDecimals(string $value, int $decimals): void
    {
        $number = new Number($value, true, $value, $value);
        $this->assertSame($decimals, $number->getValueMaxDecimals());
    }

    public static function maxDecimalsToTest(): array
    {
        return [
            ['0', 0],
            ['0.1', 1],
            ['0.01', 2],
            ['-0', 0],
            ['-0.1', 1],
            ['-0.01', 2],
        ];
    }

    /** @dataProvider minGreaterThanMaxValuesToTest */
    public function testMinValueGreaterThatMaxValueThrowsException(string $value, string $min, string $max, bool $exception): void
    {
        if ($exception) {
            $this->expectException(DomainException::class);
            $this->expectExceptionMessage('maxValueIsSmallerThanMinValue');
        } else {
            $this->expectNotToPerformAssertions();
        }
        new Number($value, true, $min, $max);
    }

    public static function minGreaterThanMaxValuesToTest(): array
    {
        return [
            ['1', '1', '0', true],
            ['0.1', '0.1', '0.01', true],
            ['-1', '-1', '-2', true],
            ['1', '1', '2', false],
            ['0.1', '0.1', '0.2', false],
            ['-1', '-2', '-1', false],
            ['0', '0', '0', false],
        ];
    }

    /** @dataProvider valueDecimalsToTest */
    public function testValidateValueDecimals(string $value, string $min, string $max, bool $exception): void
    {
        if ($exception) {
            $this->expectException(DomainException::class);
            $this->expectExceptionMessage('numberPrecision');
        } else {
            $this->expectNotToPerformAssertions();
        }
        new Number($value, true, $min, $max);
    }

    public static function valueDecimalsToTest(): array
    {
        return [
            ['0.1', '0', '1', true], //No decimal allowed
            ['0.11', '0.1', '0.2', true],
            ['-0.5', '-1', '0', true],
            ['0.1', '0.0', '1', false],
            ['0.1', '0', '1.0', false],
            ['-0.5', '-1', '0.0', false],
        ];
    }

    public function testClipExtraDecimals(): void
    {
        $number = new Number('0.01', false, '0', '1');
        $this->assertSame(0, $number->getValueMaxDecimals());
        $this->assertSame('0', $number->getValue());
        $number = new Number('0.6', false, '0', '1');
        $this->assertSame(0, $number->getValueMaxDecimals());
        $this->assertSame('0', $number->getValue());
    }

    /** @dataProvider valueLimitsToTest */
    public function testValidateValueLimits(string $value, string $min, string $max, bool $exception): void
    {
        if ($exception) {
            $this->expectException(DomainException::class);
            $this->expectExceptionMessage('enterNumberBetween');
        } else {
            $this->expectNotToPerformAssertions();
        }
        new Number($value, true, $min, $max);
    }

    public static function valueLimitsToTest(): array
    {
        return [
            ['0', '1', '2', true],
            ['2', '0', '1', true],
            ['0', '-2', '-1', true],
            ['-3', '-2', '-1', true],
            ['0.1', '0.2', '0.3', true],
            ['0.4', '0.2', '0.3', true],
            ['-0.1', '-0.3', '-0.2', true],
            ['-0.4', '-0.3', '-0.2', true],
            ['0', '0', '2', false],
            ['2', '0', '2', false],
            ['1', '0', '2', false],
            ['-3', '-3', '-1', false],
            ['-1', '-3', '-1', false],
            ['-2', '-3', '-1', false],
            ['0.1', '0.1', '0.3', false],
            ['0.3', '0.1', '0.3', false],
            ['0.2', '0.1', '0.3', false],
            ['-0.1', '-0.3', '-0.1', false],
            ['-0.3', '-0.3', '-0.1', false],
            ['-0.2', '-0.3', '-0.1', false],
        ];
    }

    public function testGetValue(): void
    {
        $this->assertSame('5', (new Number('5.00'))->getValue());
        $this->assertSame('3.4', (new Number('3.40'))->getValue());
        $this->assertSame('4', (new Number('04'))->getValue());
        $this->assertSame('4.4', (new Number('04.40'))->getValue());
        $this->assertSame('0.4', (new Number('0.40'))->getValue());
    }

    public function testComparisons(): void
    {
        $number = new Number('0');
        $this->assertTrue($number->greater(new Number('-1')));
        $this->assertTrue($number->same($number));
        $this->assertTrue($number->smaller(new Number('1')));
        $this->assertTrue($number->smallerOrEqual($number));
        $this->assertTrue($number->smallerOrEqual(new Number('1')));
        $this->assertTrue($number->different(new Number('-1')));
        $this->assertTrue($number->different(new Number('1')));
    }
}
