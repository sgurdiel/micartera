<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock\Transaction;

use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Number\Number;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountOutstandingVO;

/**
 * @covers xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountOutstandingVO
 * @uses xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
  */
class TransactionAmountOutstandingVOTest extends TestCase
{
    /**
     * @dataProvider valuesToTest
     */
    public function testValidValues(string $amount, bool $exception, string $exceptionMsg): void
    {
        if ($exception) {
            $this->expectException(DomainException::class);
            $this->expectExceptionMessage($exceptionMsg);
        }else{
            $this->expectNotToPerformAssertions();
        }
        new TransactionAmountOutstandingVO($amount);
    }

    public static function valuesToTest(): array
    {
        return [
            ['0', false, ''],
            ['0.000000001', false, ''],
            ['1', false, ''],
            ['1.000000001', false, ''],
            ['999999999.999999999', false, ''],
            ['0.0000000001', true, 'numberPrecision'],
            ['1.0000000001', true, 'numberPrecision'],
            ['0.9999999999', true, 'numberPrecision'],
            ['999999999.9999999999', true, 'numberPrecision'],
            ['1000000000', true, 'enterNumberBetween'],
            ['-0.1', true, 'enterNumberBetween'],
            ['-0.000000001', true, 'enterNumberBetween'],
        ];
    }

    /**
     * @dataProvider addsToTest
     */
    public function testAdd(string $amount, string $operand, string $result): void
    {
        $amount = new TransactionAmountOutstandingVO($amount);
        $this->assertSame($result, $amount->add(new Number($operand))->getValue());
    }

    public static function addsToTest(): array
    {
        return [
            ['1', '2', '3'],
            ['0.5', '2', '2.5'],
        ];
    }

    /**
     * @dataProvider substractsToTest
     */
    public function testSubtract(string $amount, string $operand, string $result): void
    {
        $amount = new TransactionAmountOutstandingVO($amount);
        $this->assertSame($result, $amount->subtract(new Number($operand))->getValue());
    }

    public static function substractsToTest(): array
    {
        return [
            ['2', '1', '1'],
            ['2', '0.5', '1.5'],
        ];
    }
}
