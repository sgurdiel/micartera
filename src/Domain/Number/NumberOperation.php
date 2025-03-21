<?php

namespace xVer\MiCartera\Domain\Number;

class NumberOperation
{
    /**
     * @psalm-return numeric-string
     */
    public function add(int $decimals, NumberInterface $operand1, NumberInterface $operand2): string
    {
        return bcadd($operand1->getValue(), $operand2->getValue(), $decimals);
    }

    /**
     * @psalm-return numeric-string
     */
    public function subtract(int $decimals, NumberInterface $operand1, NumberInterface $operand2): string
    {
        return bcsub($operand1->getValue(), $operand2->getValue(), $decimals);
    }

    /**
     * @psalm-return numeric-string
     */
    public function multiply(int $decimals, NumberInterface $operand1, NumberInterface $operand2): string
    {
        return bcmul($operand1->getValue(), $operand2->getValue(), $decimals);
    }

    /**
     * @psalm-return numeric-string
     */
    public function divide(int $decimals, NumberInterface $dividend, NumberInterface $divisor): string
    {
        return bcdiv($dividend->getValue(), $divisor->getValue(), $decimals);
    }

    private function compare(int $decimals, NumberInterface $operand1, NumberInterface $operand2): int
    {
        return bccomp($operand1->getValue(), $operand2->getValue(), $decimals);
    }

    public function same(int $decimals, NumberInterface $operand1, NumberInterface $operand2): bool
    {
        return 0 === $this->compare($decimals, $operand1, $operand2);
    }

    public function greater(int $decimals, NumberInterface $operand1, NumberInterface $operand2): bool
    {
        return 1 === $this->compare($decimals, $operand1, $operand2);
    }

    public function greaterOrEqual(int $decimals, NumberInterface $operand1, NumberInterface $operand2): bool
    {
        $result = $this->compare($decimals, $operand1, $operand2);

        return 1 === $result || 0 === $result;
    }

    public function smaller(int $decimals, NumberInterface $operand1, NumberInterface $operand2): bool
    {
        return -1 === $this->compare($decimals, $operand1, $operand2);
    }

    public function smallerOrEqual(int $decimals, NumberInterface $operand1, NumberInterface $operand2): bool
    {
        $result = $this->compare($decimals, $operand1, $operand2);

        return -1 === $result || 0 === $result;
    }

    public function different(int $decimals, NumberInterface $operand1, NumberInterface $operand2): bool
    {
        return 0 !== $this->compare($decimals, $operand1, $operand2);
    }

    public function percentageDifference(
        int $operandsDecimals,
        int $outputDecimals,
        NumberInterface $operand1,
        NumberInterface $operand2
    ): string {
        if ($this->same($operandsDecimals, new Number('0'), $operand1) || $this->same($operandsDecimals, new Number('0'), $operand2)) {
            $return = '0';
            if ($this->different($operandsDecimals, new Number('0'), $operand1) && $this->same($operandsDecimals, new Number('0'), $operand2)) {
                $return = '-100';
            }
            if ($this->same($operandsDecimals, new Number('0'), $operand1) && $this->different($operandsDecimals, new Number('0'), $operand2)) {
                $return = '100';
            }
            return number_format(floatval($return), $operandsDecimals, '.', '');
        }

        $divResult = new Number($this->divide(
            ($operandsDecimals+3),
            new Number(
                $this->subtract(
                    ($operandsDecimals+3),
                    $operand2,
                    $operand1
                )
            ),
            $operand1
        ), false);

        $mulResult = $this->multiply(
            ($operandsDecimals+3),
            $divResult,
            new Number('100')
        );

        $mulResultRounded = round(
            floatval($mulResult),
            $outputDecimals,
            PHP_ROUND_HALF_UP
        );

        return number_format($mulResultRounded, $outputDecimals, '.', '');
    }
}
