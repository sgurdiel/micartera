<?php

namespace xVer\MiCartera\Domain\Stock\Accounting;

class SummaryDTO
{
    /**
     * @param numeric-string $acquisitionsPrice,
     * @param numeric-string $acquisitionsExpenses,
     * @param numeric-string $liquidationsPrice,
     * @param numeric-string $liquidationsExpenses
     */
    public function __construct(
        public readonly string $acquisitionsPrice,
        public readonly string $acquisitionsExpenses,
        public readonly string $liquidationsPrice,
        public readonly string $liquidationsExpenses
    ) {
    }
}
