<?php

namespace xVer\MiCartera\Domain\Stock;

use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryInterface;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StocksCollection;

interface StockRepositoryInterface extends EntityObjectRepositoryInterface
{
    public function persist(Stock $stock): Stock;

    public function remove(Stock $stock): void;

    public function findById(string $code): ?Stock;

    public function findByCurrency(
        Currency $currency,
        ?int $limit = null,
        int $offset = 0,
        string $sortField = 'code',
        string $sortDir = 'ASC'
    ): StocksCollection;

    public function findByIdOrThrowException(string $id): Stock;
}
