<?php

namespace xVer\MiCartera\Infrastructure\Stock;

use xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryInterface;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryTrait;

class StockRepositoryInMemory extends PersistanceInMemory implements StockRepositoryInterface
{
    use StockRepositoryTrait;

    public function findById(string $code): ?Stock
    {
        /** @var Stock $persistedStock */
        foreach ($this->getPersistedObjects() as $persistedStock) {
            if (0 === strcmp($persistedStock->getId(), $code)) {
                return $persistedStock;
            }
        }
        return null;
    }

    /**
     * @return Stock[]
     */
    public function findByCurrencySorted(Currency $currency, int $limit, int $offset, string $sortField = 'code', string $sortDir = 'ASC'): array
    {
        /** @var Stock[] */
        $persistedStocks = $this->getPersistedObjects();
        $filteredStocks = array_filter($persistedStocks, function (Stock $stock) use ($currency) {
            return $stock->getPrice()->getCurrency()->getIso3() === $currency->getIso3();
        });
        usort($filteredStocks, function (Stock $a, Stock $b) use ($sortField, $sortDir) {
            if ($sortField === 'price') {
                return (
                    'ASC' === $sortDir
                    ? $a->getPrice()->getValue() <=> $b->getPrice()->getValue()
                    : $b->getPrice()->getValue() <=> $a->getPrice()->getValue()
                );
            }
            return (
                'ASC' === $sortDir
                ? strcmp($a->getId(), $b->getId())
                : strcmp($b->getId(), $a->getId())
            );
        });
        return array_slice($filteredStocks, $offset, $limit);
    }
}
