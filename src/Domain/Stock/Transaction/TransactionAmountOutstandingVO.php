<?php

namespace xVer\MiCartera\Domain\Stock\Transaction;

use xVer\MiCartera\Domain\Number\Number;
use xVer\MiCartera\Domain\Number\NumberInterface;
use xVer\MiCartera\Domain\Number\NumberOperation;

class TransactionAmountOutstandingVO extends Number
{
    /** @var numeric-string */
    final public const LOWEST_AMOUNT = '0';
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

    public function add(NumberInterface $operand): TransactionAmountOutstandingVO
    {
        return new self(
            $this->numberOperation->add(
                $this->getValueMaxDecimals(),
                $this,
                $operand
            )
        );
    }

    public function subtract(NumberInterface $operand): TransactionAmountOutstandingVO
    {
        return new self(
            $this->numberOperation->subtract(
                $this->getValueMaxDecimals(),
                $this,
                $operand
            )
        );
    }
}
