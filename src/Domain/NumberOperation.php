<?php

namespace xVer\MiCartera\Domain;

use InvalidArgumentException;
use phpDocumentor\Reflection\Types\Boolean;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;

class NumberOperation
{
    /**
     * @psalm-return numeric-string
     * @psalm-param numeric-string $operand1
     * @psalm-param numeric-string $operand2
     */
    public static function add(int $decimals, string $operand1, string $operand2): string
    {
        return bcadd($operand1, $operand2, $decimals);
    }

    /**
     * @psalm-return numeric-string
     * @psalm-param numeric-string $operand1
     * @psalm-param numeric-string $operand2
     */
    public static function subtract(int $decimals, string $operand1, string $operand2): string
    {
        return bcsub($operand1, $operand2, $decimals);
    }

    /**
     * @psalm-return numeric-string
     * @psalm-param numeric-string $operand1
     * @psalm-param numeric-string $operand2
     */
    public static function multiply(int $decimals, string $operand1, string $operand2): string
    {
        return bcmul($operand1, $operand2, $decimals);
    }

    /**
     * @psalm-return numeric-string
     * @psalm-param numeric-string $dividend
     * @psalm-param numeric-string $divisor
     */
    public static function divide(int $decimals, string $dividend, string $divisor): string
    {
        return bcdiv($dividend, $divisor, $decimals);
    }

    /**
     * @psalm-param numeric-string $operand1
     * @psalm-param numeric-string $operand2
     */
    public static function compare(int $decimals, string $operand1, string $operand2): int
    {
        return bccomp($operand1, $operand2, $decimals);
    }

    /**
     * @psalm-param numeric-string $operand1
     * @psalm-param numeric-string $operand2
     */
    public static function same(int $decimals, string $operand1, string $operand2): bool
    {
        return 0 === bccomp($operand1, $operand2, $decimals);
    }

    /**
     * @psalm-return numeric-string
     * @psalm-param numeric-string $operand1
     * @psalm-param numeric-string $operand2
     */
    public static function modulus(int $decimals, string $operand1, string $operand2): string
    {
        return bcmod($operand1, $operand2, $decimals);
    }

    /**
     * @psalm-param numeric-string $operand1
     * @psalm-param numeric-string $operand2
     */
    public static function percentageDifference(
        int $operandsDecimals,
        int $outputDecimals,
        string $operand1,
        string $operand2
    ): string {
        if (self::same($operandsDecimals, '0', $operand1) || self::same($operandsDecimals, '0', $operand2)) {
            $return = '0';
            if (!self::same($operandsDecimals, '0', $operand1) && self::same($operandsDecimals, '0', $operand2)) {
                $return = '-100';
            }
            if (self::same($operandsDecimals, '0', $operand1) && !self::same($operandsDecimals, '0', $operand2)) {
                $return = '100';
            }
            return number_format(floatval($return), $operandsDecimals, '.', '');
        }

        $divResult = self::divide(
            ($operandsDecimals+3),
            self::subtract(
                ($operandsDecimals+3),
                $operand2,
                $operand1
            ),
            $operand1
        );

        $mulResult = self::multiply(
            ($operandsDecimals+3),
            $divResult,
            '100'
        );

        $mulResultRounded = round(
            floatval($mulResult),
            $outputDecimals,
            PHP_ROUND_HALF_UP
        );

        return number_format($mulResultRounded, $outputDecimals, '.', '');
    }

    public static function validValue(string $number): void
    {
        if (
            false === filter_var($number, FILTER_VALIDATE_FLOAT, ['options' => ['decimal' => '.']])
        ) {
            throw new DomainException(
                new TranslationVO(
                    'numberFormat',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                )
            );
        }
    }

    /**
     * @psalm-param numeric-string $number
     */
    public static function validPrecision(string $number, int $precision): void
    {
        if (
            1 === self::compare(0, '0', (string) $precision)
        ) {
            throw new InvalidArgumentException();
        }
        $d = intval('1e' . $precision);
        if (
            // Ensure precision does not exceeds allowed decimal places
            0 < self::modulus(1, self::multiply(1, $number, (string) $d), '1.0')
        ) {
            throw new DomainException(
                new TranslationVO(
                    'numberPrecision',
                    ['precision' => $precision],
                    TranslationVO::DOMAIN_VALIDATORS
                )
            );
        }
    }
}
