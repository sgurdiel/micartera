<?php

namespace xVer\MiCartera\Infrastructure\AccountingMovement;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInterface;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryTrait;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine;

/**
 * @template T
 * @template-extends PersistanceDoctrine<AccountingMovement>
 */
class AccountingMovementRepositoryDoctrine extends PersistanceDoctrine implements AccountingMovementRepositoryInterface
{
    use AccountingMovementRepositoryTrait;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, AccountingMovement::class);
    }

    /**
     * @return AccountingMovement[]
     * @psalm-return AccountingMovement[]
     */
    public function findBySellTransactionId(Uuid $sellUuid): array
    {
        return $this->findBy(["sellTransaction" => $sellUuid]);
    }

    /**
     * @psalm-return AccountingMovement|null
     */
    public function findByBuyAndSellTransactionIds(Uuid $buyUuid, Uuid $sellUuid): ?AccountingMovement
    {
        return $this->findOneBy(["buyTransaction" => $buyUuid, "sellTransaction" => $sellUuid]);
    }

    /**
     * @return AccountingMovement[]
     * @psalm-return AccountingMovement[]
     */
    public function findByAccountAndYear(Account $account, int $year, int $offset, ?int $limit): array
    {
        $dateFrom = new \DateTime($year.'-01-01 00:00:00', $account->getTimeZone());
        $dateTo = new \DateTime(($year+1).'-01-01 00:00:00', $account->getTimeZone());
        $dateFrom->setTimezone(new \DateTimeZone('UTC'));
        $dateTo->setTimezone(new \DateTimeZone('UTC'));
        $qb = $this->createQueryBuilder('t')
            ->select('t, t2, t3')
            ->innerJoin('t.sellTransaction', 't2')
            ->innerJoin('t.buyTransaction', 't3')
            ->where('t2.account = :account_id')
            ->andWhere('t2.datetimeutc >= :date_from')
            ->andWhere('t2.datetimeutc < :date_to')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('date_from', $dateFrom->format('Y-m-d H:i:s'))
            ->setParameter('date_to', $dateTo->format('Y-m-d H:i:s'))
            ->orderBy('t2.datetimeutc', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        /** @var AccountingMovement[] */
        return $qb->getQuery()->getResult();
    }

    public function findYearOfOldestMovementByAccount(Account $account): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('MIN(t2.datetimeutc) AS oldestDate')
            ->innerJoin('t.sellTransaction', 't2')
            ->where('t2.account = :account_id')
            ->setParameter('account_id', $account->getId(), 'uuid');
        $query = $qb->getQuery();
        /** @var array<mixed> */
        $result = $query->execute();
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (is_array($result) && isset($result[0]["oldestDate"])) {
            /** @var string */
            $dateString = $result[0]["oldestDate"];
            $now = new \DateTime($dateString, new \DateTimeZone('UTC'));
            $now->setTimezone($account->getTimeZone());
        } else {
            $now = new \DateTime('now', $account->getTimeZone());
        }
        return (int) $now->format('Y');
    }

    public function findTotalPurchaseAndSaleByAccount(Account $account): array
    {
        $query = $this->_em->createQuery('
        SELECT
        SUM(am.amount * tb.price) buy, 
        SUM(am.amount * ts.price) sell 
        FROM xVer\MiCartera\Domain\AccountingMovement\AccountingMovement am
        JOIN am.buyTransaction tb
        JOIN am.sellTransaction ts
        WHERE ts.account = :account_id
        ');
        $query->setParameter('account_id', $account->getId(), 'uuid');
        /** @var non-empty-array<string> */
        return $query->getSingleResult();
    }

    /**
     * @return AccountingMovement[]
     * @psalm-return AccountingMovement[]
     */
    public function findByAccountStockBuyDateAfter(Account $account, Stock $stock, \DateTime $dateTime): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t, t2, t3')
            ->innerJoin('t.sellTransaction', 't2')
            ->innerJoin('t.buyTransaction', 't3')
            ->where('t3.account = :account_id')
            ->andWhere('t3.stock = :stock_id')
            ->andWhere('t3.datetimeutc > :date')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_id', $stock->getId())
            ->setParameter('date', $dateTime->format('Y-m-d H:i:s'))
            ->addOrderBy('t3.datetimeutc', 'ASC')
            ->addOrderBy('t2.datetimeutc', 'ASC');
        /** @var AccountingMovement[] */
        return $qb->getQuery()->getResult();
    }

    /**
     * @return AccountingMovement[]
     * @psalm-return AccountingMovement[]
     */
    public function findByAccountStockSellDateAfter(Account $account, Stock $stock, \DateTime $dateTime): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t, t2, t3')
            ->innerJoin('t.sellTransaction', 't2')
            ->innerJoin('t.buyTransaction', 't3')
            ->where('t3.account = :account_id')
            ->andWhere('t3.stock = :stock_id')
            ->andWhere('t2.datetimeutc > :date')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_id', $stock->getId())
            ->setParameter('date', $dateTime->format('Y-m-d H:i:s'))
            ->addOrderBy('t3.datetimeutc', 'ASC')
            ->addOrderBy('t2.datetimeutc', 'ASC');
        /** @var AccountingMovement[] */
        return $qb->getQuery()->getResult();
    }

    /**
     * @return AccountingMovement[]
     * @psalm-return AccountingMovement[]
     */
    public function findByAccountStockSellDateAtOrAfter(Account $account, Stock $stock, \DateTime $dateTime): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t, t2, t3')
            ->innerJoin('t.sellTransaction', 't2')
            ->innerJoin('t.buyTransaction', 't3')
            ->where('t2.account = :account_id')
            ->andWhere('t2.stock = :stock_id')
            ->andWhere('t2.datetimeutc >= :date')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_id', $stock->getId())
            ->setParameter('date', $dateTime->format('Y-m-d H:i:s'))
            ->addOrderBy('t3.datetimeutc', 'ASC')
            ->addOrderBy('t2.datetimeutc', 'ASC');
        /** @var AccountingMovement[] */
        return $qb->getQuery()->getResult();
    }
}
