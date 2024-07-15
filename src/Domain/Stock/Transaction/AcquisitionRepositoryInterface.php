<?php

namespace xVer\MiCartera\Domain\Stock\Transaction;

use DateTime;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryInterface;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Portfolio\SummaryVO;
use xVer\MiCartera\Domain\Stock\Stock;

interface AcquisitionRepositoryInterface extends EntityObjectRepositoryInterface
{
    public function persist(Acquisition $acquisition): Acquisition;

    public function remove(Acquisition $acquisition): void;

    public function findById(Uuid $uuid): ?Acquisition;

    public function findByIdOrThrowException(Uuid $id): Acquisition;

    public function findByAccountStockWithAmountOutstandingAndDateAtOrBefore(
        Account $account,
        Stock $stock,
        DateTime $date
    ): AcquisitionsCollection;

    public function findByAccountWithAmountOutstanding(
        Account $account,
        string $sortOrder,
        string $sortField = 'datetimeutc',
        int $limit = 1,
        int $offset = 0
    ): AcquisitionsCollection;

    public function findByStockId(
        Stock $stock,
        int $limit = 1,
        int $offset = 0
    ): AcquisitionsCollection;

    public function assertNoTransWithSameAccountStockOnDateTime(
        Account $account,
        Stock $stock,
        DateTime $datetimeutc
    ): bool;

    public function portfolioSummary(
        Account $account,
        ?Stock $stock = null
    ): SummaryVO;
}
