<?php

namespace xVer\MiCartera\Domain\Accounting;

class SummaryDTO
{
    /**
     * @param numeric-string $adquisitionsPrice,
     * @param numeric-string $adquisitionsExpenses,
     * @param numeric-string $liquidationsPrice,
     * @param numeric-string $liquidationsExpenses
     */
    public function __construct(
        public readonly string $adquisitionsPrice,
        public readonly string $adquisitionsExpenses,
        public readonly string $liquidationsPrice,
        public readonly string $liquidationsExpenses
    ) {
    }
}
