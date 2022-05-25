<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Domain;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\NumberOperation;

/**
 * @covers xVer\MiCartera\Domain\NumberOperation
 */
class NumberOperationTest extends TestCase
{
    public function testAdd(): void
    {
        $this->assertSame('5.34', NumberOperation::add(2, '4', '1.34'));
        $this->assertSame('5.34', NumberOperation::add(2, '-4', '9.34'));
        $this->assertSame('5.66', NumberOperation::add(2, '4.663', '1'));
        $this->assertSame('5.66', NumberOperation::add(2, '4.667', '1'));
        $this->assertSame('5.34', NumberOperation::add(2, '4', '1.343'));
        $this->assertSame('5.34', NumberOperation::add(2, '4', '1.347'));
        $this->assertSame('5.35', NumberOperation::add(2, '4.004', '1.347'));
        $this->assertSame('5.34', NumberOperation::add(2, '4.002', '1.347'));
        $this->assertSame('5.3400', NumberOperation::add(4, '4', '1.34'));
        $this->assertSame('5.3411', NumberOperation::add(4, '4', '1.3411'));
    }

    public function testSubtract(): void
    {
        $this->assertSame('3.59', NumberOperation::subtract(2, '6', '2.41'));
        $this->assertSame('-8.41', NumberOperation::subtract(2, '-6', '2.41'));
        $this->assertSame('4.12', NumberOperation::subtract(2, '6.127', '2'));
        $this->assertSame('4.12', NumberOperation::subtract(2, '6.122', '2'));
        $this->assertSame('4.41', NumberOperation::subtract(2, '6.413', '2'));
        $this->assertSame('4.41', NumberOperation::subtract(2, '6.417', '2'));
        $this->assertSame('3.58', NumberOperation::subtract(2, '6', '2.413'));
        $this->assertSame('3.58', NumberOperation::subtract(2, '6', '2.417'));
        $this->assertSame('3.5900', NumberOperation::subtract(4, '6', '2.41'));
        $this->assertSame('3.5845', NumberOperation::subtract(4, '6', '2.4155'));   
    }

    public function testMultiply(): void
    {
        $this->assertSame('5.36', NumberOperation::multiply(2, '4', '1.34'));
        $this->assertSame('-37.36', NumberOperation::multiply(2, '-4', '9.34'));
        $this->assertSame('13.98', NumberOperation::multiply(2, '4.663', '3'));
        $this->assertSame('14.00', NumberOperation::multiply(2, '4.667', '3'));
        $this->assertSame('5.37', NumberOperation::multiply(2, '4', '1.343'));
        $this->assertSame('5.38', NumberOperation::multiply(2, '4', '1.347'));
        $this->assertSame('5.38', NumberOperation::multiply(2, '1.347', '4'));
        $this->assertSame('5.3600', NumberOperation::multiply(4, '4', '1.34'));
        $this->assertSame('5.3644', NumberOperation::multiply(4, '4', '1.3411'));
    }

    public function testSame(): void
    {
        $this->assertTrue(NumberOperation::same(2, '5.56', '5.56'));
        $this->assertTrue(NumberOperation::same(2, '5.56', '5.567'));
        $this->assertTrue(NumberOperation::same(2, '5.567', '5.56'));
        $this->assertFalse(NumberOperation::same(2, '5.56', '5.57'));
        $this->assertTrue(NumberOperation::same(2, '5', '5.00'));
    }

    public function testDivide(): void
    {
        $this->assertSame('0.04', NumberOperation::divide(2, '4', '100'));
        $this->assertSame('-0.04', NumberOperation::divide(2, '-4', '100'));
        $this->assertSame('0.42', NumberOperation::divide(2, '428', '1000'));
        $this->assertSame('0.4280', NumberOperation::divide(4, '428', '1000'));
    }

    public function testPercentageDifference(): void
    {
        $this->assertSame('100.00', NumberOperation::percentageDifference(2, '0', '10'));
        $this->assertSame('-100.00', NumberOperation::percentageDifference(2, '3', '0'));
        $this->assertSame('0.00', NumberOperation::percentageDifference(2, '0', '0'));
        $this->assertSame('233.33', NumberOperation::percentageDifference(2, '3', '10'));
        $this->assertSame('200.00', NumberOperation::percentageDifference(2, '3', '9'));
        $this->assertSame('181.33', NumberOperation::percentageDifference(2, '3', '8.44'));
        $this->assertSame('191.67', NumberOperation::percentageDifference(2, '3', '8.75'));
        $this->assertSame('145.50', NumberOperation::percentageDifference(2, '3.666', '9'));
        $this->assertSame('34.43', NumberOperation::percentageDifference(2, '2440', '3280'));
    }
}
