<?php

namespace xVer\MiCartera\Infrastructure\Stock\Transaction;

use DateTime;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Portfolio\SummaryVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\Adquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection;
use xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine;

/**
 * @template-extends EntityObjectRepositoryDoctrine<Adquisition>
 */
class AdquisitionRepositoryDoctrine extends EntityObjectRepositoryDoctrine implements AdquisitionRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Adquisition::class);
    }

    public function persist(Adquisition $adquisition): Adquisition
    {
        $this->_em->persist($adquisition);
        return $adquisition;
    }

    public function remove(Adquisition $adquisition): void
    {
        $this->_em->remove($adquisition);
    }

    /**
     * @psalm-return Adquistion|null
     */
    public function findById(Uuid $uuid): ?Adquisition
    {
        return $this->findOneBy(['id' => $uuid]);
    }

    public function findByIdOrThrowException(Uuid $id): Adquisition
    {
        if (null === ($object = $this->findById($id))) {
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
    ): AdquisitionsCollection {
        $qb = $this->createQueryBuilder('t')
            ->where('t.account = :account_id')
            ->andWhere('t.stock = :stock_code')
            ->andWhere('t.amountOutstanding > 0')
            ->andWhere('t.datetimeutc <= :datetimeutc')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_code', $stock->getId())
            ->setParameter('datetimeutc', $date->format('Y-m-d H:i:s'))
            ->orderBy('t.datetimeutc', 'ASC');
        return new AdquisitionsCollection(
            $qb->getQuery()->setLockMode(
                $this->_em->getConnection()->isTransactionActive()
                ? LockMode::PESSIMISTIC_WRITE
                : LockMode::NONE
            )->getResult()
        );
    }

    public function findByStockId(
        Stock $stock,
        int $limit = 1,
        int $offset = 0
    ): AdquisitionsCollection {
        return new AdquisitionsCollection(
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
    ): AdquisitionsCollection {
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
        return new AdquisitionsCollection(
            $query->getResult()
        );
    }

    public function portfolioSummary(Account $account): SummaryVO
    {
        $qb = $this->createQueryBuilder('t')
            ->select(
                '
            COALESCE(SUM(t.amountOutstanding * t.price),0) adquisitionPrice, 
            COALESCE(SUM(t.amountOutstanding * s.price),0) marketPrice,
            COALESCE(SUM(t.expensesUnaccountedFor), 0) adquisitionFee
            '
            )
            ->innerJoin('t.stock', 's')
            ->where('t.account = :account_id')
            ->andWhere('t.amountOutstanding > 0')
            ->setParameter('account_id', $account->getId(), 'uuid');
        /** @var non-empty-array<string,string,string> */
        $result = $qb->getQuery()->getSingleResult();
        return new SummaryVO(
            new MoneyVO($result['adquisitionPrice'], $account->getCurrency()),
            new MoneyVO($result['adquisitionFee'], $account->getCurrency()),
            new MoneyVO($result['marketPrice'], $account->getCurrency())
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
