<?php

namespace xVer\MiCartera\Infrastructure\Stock;

use xVer\Bundle\DomainBundle\Infrastructure\RepositoryInterface;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

interface StockRepositoryInterface extends RepositoryInterface
{
    public function add(Stock $stock): Stock;

    public function update(Stock $stock): void;

    public function remove(Stock $stock, TransactionRepositoryInterface $transRepo): void;

    public function createConstraints(Stock $stock): void;

    public function removeConstraints(Stock $stock, TransactionRepositoryInterface $transRepo): void;

    public function findById(string $code): ?Stock;

    /** @return Stock[] */
    public function findByCurrencySorted(Currency $currency, int $limit, int $offset, string $sortField = 'code', string $sortDir = 'ASC'): array;

    public function findByIdOrThrowException(string $id): Stock;
}
