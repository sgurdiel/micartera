<?php declare(strict_types=1);

namespace Tests\unit\Domain\Number;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Number\Number;
use xVer\MiCartera\Domain\Number\NumberOperation;

/**
 * @covers xVer\MiCartera\Domain\Number\NumberOperation
 * @uses xVer\MiCartera\Domain\Number\Number
 */
class NumberOperationTest extends TestCase
{
    private static NumberOperation $numberOperation;

    public static function setUpBeforeClass(): void
    {
        self::$numberOperation = new NumberOperation();
    }
    
    public function testAdd(): void
    {
        $this->assertSame('5.34', self::$numberOperation->add(2, new Number('4'), new Number('1.34')));
        $this->assertSame('5.34', self::$numberOperation->add(2, new Number('-4'), new Number('9.34')));
        $this->assertSame('5.66', self::$numberOperation->add(2, new Number('4.663'), new Number('1')));
        $this->assertSame('5.66', self::$numberOperation->add(2, new Number('4.667'), new Number('1')));
        $this->assertSame('5.34', self::$numberOperation->add(2, new Number('4'), new Number('1.343')));
        $this->assertSame('5.34', self::$numberOperation->add(2, new Number('4'), new Number('1.347')));
        $this->assertSame('5.35', self::$numberOperation->add(2, new Number('4.004'), new Number('1.347')));
        $this->assertSame('5.34', self::$numberOperation->add(2, new Number('4.002'), new Number('1.347')));
        $this->assertSame('5.3400', self::$numberOperation->add(4, new Number('4'), new Number('1.34')));
        $this->assertSame('5.3411', self::$numberOperation->add(4, new Number('4'), new Number('1.3411')));
    }

    public function testSubtract(): void
    {
        $this->assertSame('3.59', self::$numberOperation->subtract(2, new Number('6'), new Number('2.41')));
        $this->assertSame('-8.41', self::$numberOperation->subtract(2, new Number('-6'), new Number('2.41')));
        $this->assertSame('4.12', self::$numberOperation->subtract(2, new Number('6.127'), new Number('2')));
        $this->assertSame('4.12', self::$numberOperation->subtract(2, new Number('6.122'), new Number('2')));
        $this->assertSame('4.41', self::$numberOperation->subtract(2, new Number('6.413'), new Number('2')));
        $this->assertSame('4.41', self::$numberOperation->subtract(2, new Number('6.417'), new Number('2')));
        $this->assertSame('3.58', self::$numberOperation->subtract(2, new Number('6'), new Number('2.413')));
        $this->assertSame('3.58', self::$numberOperation->subtract(2, new Number('6'), new Number('2.417')));
        $this->assertSame('3.5900', self::$numberOperation->subtract(4, new Number('6'), new Number('2.41')));
        $this->assertSame('3.5845', self::$numberOperation->subtract(4, new Number('6'), new Number('2.4155')));   
    }

    public function testMultiply(): void
    {
        $this->assertSame('5.36', self::$numberOperation->multiply(2, new Number('4'), new Number('1.34')));
        $this->assertSame('-37.36', self::$numberOperation->multiply(2, new Number('-4'), new Number('9.34')));
        $this->assertSame('13.98', self::$numberOperation->multiply(2, new Number('4.663'), new Number('3')));
        $this->assertSame('14.00', self::$numberOperation->multiply(2, new Number('4.667'), new Number('3')));
        $this->assertSame('5.37', self::$numberOperation->multiply(2, new Number('4'), new Number('1.343')));
        $this->assertSame('5.38', self::$numberOperation->multiply(2, new Number('4'), new Number('1.347')));
        $this->assertSame('5.38', self::$numberOperation->multiply(2, new Number('1.347'), new Number('4')));
        $this->assertSame('5.3600', self::$numberOperation->multiply(4, new Number('4'), new Number('1.34')));
        $this->assertSame('5.3644', self::$numberOperation->multiply(4, new Number('4'), new Number('1.3411')));
    }

    public function testSame(): void
    {
        $this->assertTrue(self::$numberOperation->same(2, new Number('5.56'), new Number('5.56')));
        $this->assertTrue(self::$numberOperation->same(2, new Number('5.56'), new Number('5.567')));
        $this->assertTrue(self::$numberOperation->same(2, new Number('5.567'), new Number('5.56')));
        $this->assertFalse(self::$numberOperation->same(2, new Number('5.56'), new Number('5.57')));
        $this->assertTrue(self::$numberOperation->same(2, new Number('5'), new Number('5.00')));
    }

    public function testDivide(): void
    {
        $this->assertSame('0.04', self::$numberOperation->divide(2, new Number('4'), new Number('100')));
        $this->assertSame('-0.04', self::$numberOperation->divide(2, new Number('-4'), new Number('100')));
        $this->assertSame('0.42', self::$numberOperation->divide(2, new Number('428'), new Number('1000')));
        $this->assertSame('0.4280', self::$numberOperation->divide(4, new Number('428'), new Number('1000')));
    }

    public function testPercentageDifference(): void
    {
        $this->assertSame('100.00', self::$numberOperation->percentageDifference(2, 2, new Number('0'), new Number('10')));
        $this->assertSame('-100.00', self::$numberOperation->percentageDifference(2, 2, new Number('3'), new Number('0')));
        $this->assertSame('0.00', self::$numberOperation->percentageDifference(2, 2, new Number('0'), new Number('0')));
        $this->assertSame('233.33', self::$numberOperation->percentageDifference(2, 2, new Number('3'), new Number('10')));
        $this->assertSame('200.00', self::$numberOperation->percentageDifference(2, 2, new Number('3'), new Number('9')));
        $this->assertSame('181.33', self::$numberOperation->percentageDifference(2, 2, new Number('3'), new Number('8.44')));
        $this->assertSame('191.67', self::$numberOperation->percentageDifference(2, 2, new Number('3'), new Number('8.75')));
        $this->assertSame('145.50', self::$numberOperation->percentageDifference(3, 2, new Number('3.666'), new Number('9')));
        $this->assertSame('34.43', self::$numberOperation->percentageDifference(2, 2, new Number('2440'), new Number('3280')));
    }

    /** @dataProvider comparisonsToTest */
    public function testComparisons(bool $result, string $op, int $decimals, string $num1, string $num2): void
    {
        if ($result) {
            $this->assertTrue(self::$numberOperation->{$op}($decimals, new Number($num1), new Number($num2)));
        } else {
            $this->assertFalse(self::$numberOperation->{$op}($decimals, new Number($num1), new Number($num2)));
        }
    }

    public static function comparisonsToTest(): array
    {
        return [
            [true, 'same', 0, '1', '1'],
            [false, 'same', 0, '1', '2'],
            [true, 'same', 1, '1', '1'],
            [false, 'same', 1, '1', '2'],
            [true, 'same', 1, '1.0', '1'],
            [false, 'same', 1, '1.0', '2'],
            [true, 'greater', 0, '1', '0'],
            [false, 'greater', 0, '0', '1'],
            [true, 'greater', 1, '1.1', '0.4'],
            [false, 'greater', 1, '0.4', '1.1'],
            [true, 'greaterOrEqual', 0, '1', '0'],
            [false, 'greaterOrEqual', 0, '0', '1'],
            [true, 'greaterOrEqual', 1, '1.1', '0.4'],
            [false, 'greaterOrEqual', 1, '0.4', '1.1'],
            [true, 'greaterOrEqual', 0, '1', '1'],
            [true, 'greaterOrEqual', 1, '1.1', '1.1'],
            [true, 'smaller', 0, '0', '1'],
            [false, 'smaller', 0, '1', '0'],
            [true, 'smaller', 1, '0.4', '1.1'],
            [false, 'smaller', 1, '1.1', '0.4'],
            [true, 'smallerOrEqual', 0, '0', '1'],
            [false, 'smallerOrEqual', 0, '1', '0'],
            [true, 'smallerOrEqual', 1, '0.4', '1.1'],
            [false, 'smallerOrEqual', 1, '1.1', '0.4'],            
            [true, 'smallerOrEqual', 1, '1.1', '1.1'],
            [true, 'smallerOrEqual', 0, '1', '1'],
            [true, 'different', 0, '1', '2'],
            [true, 'different', 1, '1.1', '2.2'],
            [false, 'different', 1, '1.0', '1'],
        ];
    }
}
