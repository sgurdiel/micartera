<?php

namespace xVer\MiCartera\Domain\Stock\Accounting;

use DateTime;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Accounting\Movement;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Accounting\SummaryVO;
use xVer\MiCartera\Domain\Stock\Accounting\MovementsCollection;
use xVer\MiCartera\Domain\Stock\Stock;

interface MovementRepositoryInterface extends EntityObjectRepositoryInterface
{
    public function persist(Movement $movement): Movement;

    public function remove(Movement $movement): void;

    public function findByIdOrThrowException(Uuid $acquisitionUuid, Uuid $liquidationUuid): Movement;

    public function findByAccountAndYear(
        Account $account,
        int $year,
        ?int $limit = 1,
        int $offset = 0
    ): MovementsCollection;

    public function accountingSummaryByAccount(Account $account, int $displayedYear): SummaryVO;

    public function findByAccountStockAcquisitionDateAfter(
        Account $account,
        Stock $stock,
        DateTime $dateTime
    ): MovementsCollection;
}
