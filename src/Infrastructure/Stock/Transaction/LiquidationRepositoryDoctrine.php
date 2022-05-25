<?php

namespace xVer\MiCartera\Infrastructure\Stock\Transaction;

use DateTime;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationsCollection;
use xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine;

/**
 * @template-extends EntityObjectRepositoryDoctrine<Liquidation>
 */
class LiquidationRepositoryDoctrine extends EntityObjectRepositoryDoctrine implements LiquidationRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Liquidation::class);
    }

    public function persist(Liquidation $liquidation): Liquidation
    {
        $this->_em->persist($liquidation);
        return $liquidation;
    }

    public function remove(Liquidation $liquidation): void
    {
        $this->_em->remove($liquidation);
    }

    /**
     * @psalm-return Liquidation|null
     */
    public function findById(Uuid $uuid): ?Liquidation
    {
        return $this->findOneBy(['id' => $uuid]);
    }

    public function findByIdOrThrowException(Uuid $id): Liquidation
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

    public function findByStockId(Stock $stock, int $limit = 1, int $offset = 0): LiquidationsCollection
    {
        return new LiquidationsCollection(
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

    public function findByAccountStockAndDateAtOrAfter(
        Account $account,
        Stock $stock,
        DateTime $date
    ): LiquidationsCollection {
        $qb = $this->createQueryBuilder('t')
            ->where('t.account = :account_id')
            ->andWhere('t.stock = :stock_code')
            ->andWhere('t.datetimeutc >= :datetimeutc')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_code', $stock->getId())
            ->setParameter('datetimeutc', $date->format('Y-m-d H:i:s'))
            ->orderBy('t.datetimeutc', 'ASC');
        return new LiquidationsCollection(
            $qb->getQuery()->setLockMode(
                $this->_em->getConnection()->isTransactionActive()
                ? LockMode::PESSIMISTIC_WRITE
                : LockMode::NONE
            )->getResult()
        );
    }
}
