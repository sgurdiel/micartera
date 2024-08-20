<?php

namespace xVer\MiCartera\Infrastructure\Stock\Accounting;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Accounting\Movement;
use xVer\MiCartera\Domain\Stock\Accounting\MovementsCollection;
use xVer\MiCartera\Domain\Stock\Accounting\MovementRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Accounting\SummaryDTO;
use xVer\MiCartera\Domain\Stock\Accounting\SummaryVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine;

/**
 * @template-extends EntityObjectRepositoryDoctrine<Movement>
 */
class MovementRepositoryDoctrine extends EntityObjectRepositoryDoctrine implements MovementRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Movement::class);
    }

    public function persist(Movement $movement): Movement
    {
        $this->getEntityManager()->persist($movement);
        return $movement;
    }

    public function remove(Movement $movement): void
    {
        $this->getEntityManager()->remove($movement);
    }

    public function findByIdOrThrowException(Uuid $acquisitionUuid, Uuid $liquidationUuid): Movement
    {
        $object = $this->findOneBy(["acquisition" => $acquisitionUuid, "liquidation" => $liquidationUuid]);
        if (
            null === ($object)
        ) {
            throw new DomainException(
                new TranslationVO(
                    'expectedPersistedObjectNotFound',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'accountingmovement'
            );
        }
        return $object;
    }

    public function findByAccountAndYear(
        Account $account,
        int $year,
        ?int $limit = 1,
        int $offset = 0
    ): MovementsCollection {
        $dateFrom = new DateTime($year.'-01-01 00:00:00', $account->getTimeZone());
        $dateTo = new DateTime(($year+1).'-01-01 00:00:00', $account->getTimeZone());
        $dateFrom->setTimezone(new DateTimeZone('UTC'));
        $dateTo->setTimezone(new DateTimeZone('UTC'));
        $qb = $this->createQueryBuilder('t')
            ->select('t, t2, t3')
            ->innerJoin('t.liquidation', 't2')
            ->innerJoin('t.acquisition', 't3')
            ->where('t2.account = :account_id')
            ->andWhere('t2.datetimeutc >= :date_from')
            ->andWhere('t2.datetimeutc < :date_to')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('date_from', $dateFrom->format('Y-m-d H:i:s'))
            ->setParameter('date_to', $dateTo->format('Y-m-d H:i:s'))
            ->orderBy('t3.datetimeutc', 'ASC')
            ->addOrderBy('t2.datetimeutc', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        /** @var Movement[] */
        return new MovementsCollection(
            $qb->getQuery()->getResult()
        );
    }

    public function accountingSummaryByAccount(Account $account, int $displayedYear): SummaryVO
    {
        $qb = $this->createQueryBuilder('a')
            ->select(
                '
            COALESCE(SUM(a.acquisitionPrice),0) acquisitionPrice,
            COALESCE(SUM(a.acquisitionExpenses),0) acquisitionExpenses,
            COALESCE(SUM(a.liquidationPrice),0) liquidationPrice,
            COALESCE(SUM(a.liquidationExpenses),0) liquidationExpenses,
            MIN(ts.datetimeutc) firstDateTimeUtc
            '
            )
            ->innerJoin('a.liquidation', 'ts')
            ->where('ts.account = :account_id')
            ->setParameter('account_id', $account->getId(), 'uuid');
        /** @var non-empty-array<string,string,string,string> */
        $allTimeresult = $qb->getQuery()->getSingleResult();
        $summaryAllTimeDTO = new SummaryDTO(
            $allTimeresult['acquisitionPrice'],
            $allTimeresult['acquisitionExpenses'],
            $allTimeresult['liquidationPrice'],
            $allTimeresult['liquidationExpenses']
        );

        $qb = $this->createQueryBuilder('a')
            ->select(
                '
            COALESCE(SUM(a.acquisitionPrice),0) acquisitionPrice,
            COALESCE(SUM(a.acquisitionExpenses),0) acquisitionExpenses,
            COALESCE(SUM(a.liquidationPrice),0) liquidationPrice,
            COALESCE(SUM(a.liquidationExpenses),0) liquidationExpenses
            '
            )
            ->innerJoin('a.liquidation', 'ts')
            ->where('ts.account = :account_id')
            ->andWhere('ts.datetimeutc >= :date_from')
            ->andWhere('ts.datetimeutc < :date_to')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter(
                'date_from',
                (new DateTime($displayedYear.'-01-01 00:00:00', $account->getTimeZone()))
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d H:i:s')
            )
            ->setParameter(
                'date_to',
                (new DateTime(($displayedYear+1).'-01-01 00:00:00', $account->getTimeZone()))
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d H:i:s')
            );
        /** @var non-empty-array<string,string,string,string> */
        $displayedYearResult = $qb->getQuery()->getSingleResult(Query::HYDRATE_OBJECT);
        $summaryDisplayedYearDTO = new SummaryDTO(
            $displayedYearResult['acquisitionPrice'],
            $displayedYearResult['acquisitionExpenses'],
            $displayedYearResult['liquidationPrice'],
            $displayedYearResult['liquidationExpenses']
        );

        return new SummaryVO(
            $account,
            $displayedYear,
            $allTimeresult['firstDateTimeUtc'] ? DateTime::createFromFormat('Y-m-d H:i:s', $allTimeresult['firstDateTimeUtc'], new DateTimeZone('UTC')) : null,
            $summaryAllTimeDTO,
            $summaryDisplayedYearDTO
        );

    }

    public function findByAccountStockAcquisitionDateAfter(
        Account $account,
        Stock $stock,
        DateTime $dateTime
    ): MovementsCollection {
        $qb = $this->createQueryBuilder('a')
            ->select('a, t2, t3')
            ->innerJoin('a.liquidation', 't2')
            ->innerJoin('a.acquisition', 't3')
            ->where('t3.account = :account_id')
            ->andWhere('t3.stock = :stock_id')
            ->andWhere('t3.datetimeutc > :date')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_id', $stock->getId())
            ->setParameter('date', $dateTime->format('Y-m-d H:i:s'))
            ->addOrderBy('t3.datetimeutc', 'ASC')
            ->addOrderBy('t2.datetimeutc', 'ASC');
        return new MovementsCollection(
            $qb->getQuery()->getResult()
        );
    }
}
