<?php

namespace xVer\MiCartera\Domain\Number;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;

class Number implements NumberInterface
{
    private int $maxDecimals = 0;

    /**
     * @psalm-param numeric-string $value
     * @psalm-param numeric-string $minValue
     * @psalm-param numeric-string $maxValue
     */
    public function __construct(
        private string $value = '0.00',
        private readonly bool $requirePrecision = true,
        private readonly string $minValue = '-9999999999999.9999999999999',
        private readonly string $maxValue = '9999999999999.9999999999999',
        private NumberOperation $numberOperation = new NumberOperation()
    ) {
        $this->validateMinMax();
        $this->validateFormat($this->value);
        $this->trimUnnecessaryZerosInValue();
        $this->requirePrecision
        ? $this->validateValueDecimals()
        : $this->clipValueExtraDecimals();
        $this->validateValueLimits();
    }

    private function validateMinMax(): void
    {
        $this->validateFormat($this->minValue);
        $this->validateFormat($this->maxValue);

        $decimalsMin = $this->getDecimalPlaces($this->minValue);
        $decimalsMax = $this->getDecimalPlaces($this->maxValue);
        $this->maxDecimals = $decimalsMin > $decimalsMax ? $decimalsMin : $decimalsMax;

        if (1 === bccomp($this->minValue, $this->maxValue, $this->maxDecimals)) {
            throw new DomainException(
                new TranslationVO(
                    'maxValueIsSmallerThanMinValue',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                )
            );
        }
    }

    private function validateFormat(string $value): void
    {
        if (
            0 === preg_match('/^(?:-)?\d+(?:\.{1}\d+)?$/', $value)
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

    public function getValueMaxDecimals(): int
    {
        return $this->maxDecimals;
    }

    private function validateValueDecimals(): void
    {
        if ($this->getDecimalPlaces($this->value) > $this->maxDecimals) {
            throw new DomainException(
                new TranslationVO(
                    'numberPrecision',
                    ['precision' => $this->maxDecimals],
                    TranslationVO::DOMAIN_VALIDATORS
                )
            );
        }
    }

    private function clipValueExtraDecimals(): void
    {
        $d = intval('1e' . $this->maxDecimals);
        $this->value = bcdiv(
            bcmul(
                $this->getValue(),
                (string) $d,
                0
            ),
            (string) $d,
            $this->maxDecimals
        );
    }

    private function getDecimalPlaces(string $value): int
    {
        $aux = explode('.', $value);
        return isset($aux[1]) ? strlen($aux[1]) : 0;
    }

    private function validateValueLimits(): void
    {
        if (
            -1 === bccomp($this->getValue(), $this->minValue, $this->maxDecimals)
            ||
            1 === bccomp($this->getValue(), $this->maxValue, $this->maxDecimals)
        ) {
            throw new DomainException(
                new TranslationVO(
                    'enterNumberBetween',
                    ['minimum' => $this->minValue, 'maximum' => $this->maxValue],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'number'
            );
        }
    }

    private function trimUnnecessaryZerosInValue(): void
    {
        $aux = explode('.', $this->value);
        $l = $aux[0] === '0' ? $aux[0] : ltrim($aux[0], '0');
        $r = isset($aux[1]) && strlen(rtrim($aux[1], '0')) ? '.' . rtrim($aux[1], '0') : '';
        /** @var numeric-string */
        $this->value = $l.$r;
    }

    /**
     * @psalm-return numeric-string
     * this operation is guaranteed to pruduce a numeric-string, but inference can't understand it
     * @psalm-suppress LessSpecificReturnStatement
     * this operation is guaranteed to pruduce a numeric-string, but inference can't understand it
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public function greater(NumberInterface $operand): bool
    {
        return $this->numberOperation->greater($this->maxDecimals, $this, $operand);
    }

    public function same(NumberInterface $operand): bool
    {
        return $this->numberOperation->same($this->maxDecimals, $this, $operand);
    }

    public function smaller(NumberInterface $operand): bool
    {
        return $this->numberOperation->smaller($this->maxDecimals, $this, $operand);
    }

    public function smallerOrEqual(NumberInterface $operand): bool
    {
        return $this->numberOperation->smallerOrEqual($this->maxDecimals, $this, $operand);
    }

    public function different(NumberInterface $operand): bool
    {
        return $this->numberOperation->different($this->maxDecimals, $this, $operand);
    }
}
