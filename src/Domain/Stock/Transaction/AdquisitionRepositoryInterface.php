<?php

namespace xVer\MiCartera\Domain\Stock\Transaction;

use DateTime;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryInterface;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Portfolio\SummaryVO;
use xVer\MiCartera\Domain\Stock\Stock;

interface AdquisitionRepositoryInterface extends EntityObjectRepositoryInterface
{
    public function persist(Adquisition $adquisition): Adquisition;

    public function remove(Adquisition $adquisition): void;

    public function findById(Uuid $uuid): ?Adquisition;

    public function findByIdOrThrowException(Uuid $id): Adquisition;

    public function findByAccountStockWithAmountOutstandingAndDateAtOrBefore(
        Account $account,
        Stock $stock,
        DateTime $date
    ): AdquisitionsCollection;

    public function findByAccountWithAmountOutstanding(
        Account $account,
        string $sortOrder,
        string $sortField = 'datetimeutc',
        int $limit = 1,
        int $offset = 0
    ): AdquisitionsCollection;

    public function findByStockId(
        Stock $stock,
        int $limit = 1,
        int $offset = 0
    ): AdquisitionsCollection;

    public function assertNoTransWithSameAccountStockOnDateTime(
        Account $account,
        Stock $stock,
        DateTime $datetimeutc
    ): bool;

    public function portfolioSummary(Account $account): SummaryVO;
}
