<?php declare(strict_types=1);

namespace Tests\unit\Domain\Stock\Transaction;

use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Number\Number;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO;

/**
 * @covers xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO
 * @uses xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
  */
class TransactionAmountVOTest extends TestCase
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
        new TransactionAmountVO($amount);
    }

    public static function valuesToTest(): array
    {
        return [
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
     * @dataProvider dividesToTest
     */
    public function testDivide(string $amount, string $divisor, string $result): void
    {
        $amount = new TransactionAmountVO($amount);
        $this->assertSame($result, $amount->divide(new Number($divisor))->getValue());
    }

    public static function dividesToTest(): array
    {
        return [
            ['1', '2', '0.5'],
            ['0.5', '2', '0.25'],
        ];
    }
}
