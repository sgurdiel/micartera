<?php

namespace xVer\MiCartera\Domain\Stock\Transaction;

use DateTime;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryInterface;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Stock;

interface LiquidationRepositoryInterface extends EntityObjectRepositoryInterface
{
    public function persist(Liquidation $liquidation): Liquidation;

    public function remove(Liquidation $liquidation): void;

    public function findByAccountStockAndDateAtOrAfter(
        Account $account,
        Stock $stock,
        DateTime $date
    ): LiquidationsCollection;

    public function findById(Uuid $uuid): ?Liquidation;

    public function findByIdOrThrowException(Uuid $id): Liquidation;

    public function findByStockId(
        Stock $stock,
        int $limit = 1,
        int $offset = 0
    ): LiquidationsCollection;

    public function assertNoTransWithSameAccountStockOnDateTime(
        Account $account,
        Stock $stock,
        DateTime $datetimeutc
    ): bool;
}
