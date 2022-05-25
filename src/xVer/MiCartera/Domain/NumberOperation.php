<?php

namespace xVer\MiCartera\Domain;

class NumberOperation
{
    /**
     * @phpstan-return string
     * @psalm-return numeric-string
     * @phpstan-param string $operand1
     * @psalm-param numeric-string $operand1
     * @phpstan-param string $operand2
     * @psalm-param numeric-string $operand2
     */
    public static function add(int $decimals, string $operand1, string $operand2): string
    {
        return bcadd($operand1, $operand2, $decimals);
    }

    /**
     * @phpstan-return string
     * @psalm-return numeric-string
     * @phpstan-param string $operand1
     * @psalm-param numeric-string $operand1
     * @phpstan-param string $operand2
     * @psalm-param numeric-string $operand2
     */
    public static function subtract(int $decimals, string $operand1, string $operand2): string
    {
        return bcsub($operand1, $operand2, $decimals);
    }

    /**
     * @phpstan-param string $operand1
     * @psalm-param numeric-string $operand1
     * @phpstan-param string $operand2
     * @psalm-param numeric-string $operand2
     */
    public static function percentageDifference(int $decimals, string $operand1, string $operand2): string
    {
        if (self::same($decimals, '0', $operand1) || self::same($decimals, '0', $operand2)) {
            $return = '0';
            if (!self::same($decimals, '0', $operand1) && self::same($decimals, '0', $operand2)) {
                $return = '-100';
            }
            if (self::same($decimals, '0', $operand1) && !self::same($decimals, '0', $operand2)) {
                $return = '100';
            }
            return number_format(floatval($return), $decimals, '.', '');
        }

        $divResult = self::divide(
            ($decimals+3),
            self::subtract(
                ($decimals+3),
                $operand2,
                $operand1
            ),
            $operand1
        );

        $mulResult = self::multiply(
            ($decimals+3),
            $divResult,
            '100'
        );

        $mulResultRounded = round(
            floatval($mulResult),
            $decimals,
            PHP_ROUND_HALF_UP
        );

        return number_format($mulResultRounded, $decimals, '.', '');
    }

    /**
     * @phpstan-param string $operand1
     * @psalm-param numeric-string $operand1
     * @phpstan-param string $operand2
     * @psalm-param numeric-string $operand2
     */
    public static function same(int $decimals, string $operand1, string $operand2): bool
    {
        return 0 === bccomp($operand1, $operand2, $decimals);
    }

    /**
     * @phpstan-return string
     * @psalm-return numeric-string
     * @phpstan-param string $operand1
     * @psalm-param numeric-string $operand1
     * @phpstan-param string $operand2
     * @psalm-param numeric-string $operand2
     */
    public static function multiply(int $decimals, string $operand1, string $operand2): string
    {
        return bcmul($operand1, $operand2, $decimals);
    }

    /**
     * @phpstan-return string
     * @psalm-return numeric-string
     * @phpstan-param string $operand1
     * @psalm-param numeric-string $operand1
     * @phpstan-param string $operand2
     * @psalm-param numeric-string $operand2
     */
    public static function divide(int $decimals, string $operand1, string $operand2): string
    {
        // @phpstan-ignore-next-line
        return bcdiv($operand1, $operand2, $decimals);
    }
}
