<?php

namespace xVer\MiCartera\Domain\Stock\Transaction;

use xVer\MiCartera\Domain\Number\Number;
use xVer\MiCartera\Domain\Number\NumberInterface;
use xVer\MiCartera\Domain\Number\NumberOperation;

class TransactionAmountVO extends Number
{
    /** @var numeric-string */
    final public const LOWEST_AMOUNT = '0.000000001';
    final public const HIGHEST_AMOUNT = '999999999.999999999';
    private NumberOperation $numberOperation;

    /**
     * @psalm-param numeric-string $value
     */
    public function __construct(string $value)
    {
        parent::__construct($value, true, self::LOWEST_AMOUNT, self::HIGHEST_AMOUNT);
        $this->numberOperation = new NumberOperation();
    }

    public function divide(NumberInterface $operand): TransactionAmountVO
    {
        return new self(
            $this->numberOperation->divide(
                $this->getValueMaxDecimals(),
                $this,
                $operand
            )
        );
    }
}
