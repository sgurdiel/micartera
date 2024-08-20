<?php

namespace xVer\MiCartera\Infrastructure\Stock\Transaction;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Portfolio\SummaryVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\Acquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection;
use xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine;

/**
 * @template-extends EntityObjectRepositoryDoctrine<Acquisition>
 */
class AcquisitionRepositoryDoctrine extends EntityObjectRepositoryDoctrine implements AcquisitionRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Acquisition::class);
    }

    public function persist(Acquisition $acquisition): Acquisition
    {
        $this->getEntityManager()->persist($acquisition);
        return $acquisition;
    }

    public function remove(Acquisition $acquisition): void
    {
        $this->getEntityManager()->remove($acquisition);
    }

    /**
     * @psalm-return Acquistion|null
     */
    public function findById(Uuid $uuid): ?Acquisition
    {
        return $this->findOneBy(['id' => $uuid]);
    }

    public function findByIdOrThrowException(Uuid $id): Acquisition
    {
        $object = $this->findById($id);
        if (null === ($object)) {
            throw new DomainException(
                new TranslationVO(
                    'expectedPersistedObjectNotFound',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'transaction'
            );
        }
        return $object;
    }

    public function findByAccountStockWithAmountOutstandingAndDateAtOrBefore(
        Account $account,
        Stock $stock,
        DateTime $date
    ): AcquisitionsCollection {
        $qb = $this->createQueryBuilder('t')
            ->where('t.account = :account_id')
            ->andWhere('t.stock = :stock_code')
            ->andWhere('t.amountOutstanding > 0')
            ->andWhere('t.datetimeutc <= :datetimeutc')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_code', $stock->getId())
            ->setParameter('datetimeutc', $date->format('Y-m-d H:i:s'))
            ->orderBy('t.datetimeutc', 'ASC');
        return new AcquisitionsCollection(
            $qb->getQuery()->setLockMode(
                $this->getEntityManager()->getConnection()->isTransactionActive()
                ? LockMode::PESSIMISTIC_WRITE
                : LockMode::NONE
            )->getResult()
        );
    }

    public function findByStockId(
        Stock $stock,
        int $limit = 1,
        int $offset = 0
    ): AcquisitionsCollection {
        return new AcquisitionsCollection(
            $this->findBy(
                ['stock' => $stock->getId()],
                ['datetimeutc' => 'ASC'],
                $limit,
                $offset
            )
        );
    }

    public function assertNoTransWithSameAccountStockOnDateTime(
        Account $account,
        Stock $stock,
        DateTime $datetimeutc
    ): bool {
        $qb = $this->createQueryBuilder('t')
            ->where('t.account = :account_id')
            ->andWhere('t.stock = :stock_code')
            ->andWhere('t.datetimeutc = :datetimeutc')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_code', $stock->getId())
            ->setParameter('datetimeutc', $datetimeutc->format('Y-m-d H:i:s'));
        return null === $qb->getQuery()->getOneOrNullResult();
    }

    public function findByAccountWithAmountOutstanding(
        Account $account,
        string $sortOrder,
        string $sortField = 'datetimeutc',
        int $limit = 1,
        int $offset = 0
    ): AcquisitionsCollection {
        $qb = $this->createQueryBuilder('t')
            ->select('t, s')
            ->innerJoin('t.stock', 's')
            ->where('t.account = :account_id')
            ->andWhere('t.amountOutstanding > 0')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->orderBy('t.'.$this->sortFieldToString($sortField), $this->sortOrderToString($sortOrder));
        if (0 < $limit) {
            $qb->setFirstResult($offset)->setMaxResults($limit);
        }
        $query = $qb->getQuery();
        return new AcquisitionsCollection(
            $query->getResult()
        );
    }

    public function portfolioSummary(Account $account, ?Stock $stock = null): SummaryVO
    {
        $and = [
            't.account = :account_id',
            't.amountOutstanding > 0'
        ];
        $parameters = new ArrayCollection([]);
        $parameters->add(new Parameter('account_id', $account->getId(), 'uuid'));
        if (is_null($stock) === false) {
            $and[] = 's.code = :stock_code';
            $parameters->add(new Parameter('stock_code', $stock->getId(), 'string'));
        }
        $qb = $this->createQueryBuilder('t')
            ->select(
                '
            COALESCE(SUM(t.amountOutstanding * t.price),0) acquisitionPrice,
            COALESCE(SUM(t.amountOutstanding * s.price),0) marketPrice,
            COALESCE(SUM(t.expensesUnaccountedFor), 0) acquisitionFee
            '
            )
            ->innerJoin('t.stock', 's')
            ->where($and)
            ->setParameters($parameters);
        /** @var non-empty-array<string,string,string> */
        $result = $qb->getQuery()->getSingleResult();
        return new SummaryVO(
            new MoneyVO($result['acquisitionPrice'], $account->getCurrency()),
            new MoneyVO($result['acquisitionFee'], $account->getCurrency()),
            new MoneyVO($result['marketPrice'], $account->getCurrency()),
            $account->getCurrency()
        );
    }

    private function sortFieldToString(string $sortField): string
    {
        return 'amount' === $sortField ? 'amount' : 'datetimeutc';
    }

    private function sortOrderToString(string $sortOrder): string
    {
        return 'ASC' === $sortOrder ? 'ASC' : 'DESC';
    }
}
