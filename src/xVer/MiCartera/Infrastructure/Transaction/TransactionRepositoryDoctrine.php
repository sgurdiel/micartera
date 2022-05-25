<?php

namespace xVer\MiCartera\Infrastructure\Transaction;

use Doctrine\Persistence\ManagerRegistry;
use xVer\MiCartera\Domain\Transaction\Transaction;
use Symfony\Component\Uid\Uuid;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryTrait;

/**
 * @template T
 * @template-extends PersistanceDoctrine<Transaction>
 */
final class TransactionRepositoryDoctrine extends PersistanceDoctrine implements TransactionRepositoryInterface
{
    use TransactionRepositoryTrait;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Transaction::class);
    }

    /**
     * @psalm-return Transaction
     */
    public function findById(Uuid $uuid): ?Transaction
    {
        return $this->findOneBy(['id' => $uuid]);
    }

    /**
     * @return Transaction[]
     * @psalm-return Transaction[]
     */
    public function findByStockId(Stock $stock, int $limit = 1, int $offset = 0): array
    {
        return $this->findBy(['stock' => $stock->getId()], [], $limit, $offset);
    }

    /**
     * @return Transaction[]
     * @psalm-return Transaction[]
     */
    public function findByAccount(
        Account $account,
        ?int $limit,
        int $offset,
        ?string $sortField,
        ?string $sortOrder
    ): array {
        $sort = (
            is_null($sortOrder) || is_null($sortField)
            ? []
            : [$this->sortFieldToString($sortField) => $this->sortOrderToString($sortOrder)]
        );
        return $this->findBy(['account' => $account], $sort, $limit, $offset);
    }

    protected function assertNoTransWithSameAccountStockOnDateTime(Account $account, Stock $stock, \DateTime $datetimeutc): bool
    {
        return (null === $this->findOneBy(['account' => $account, 'stock' => $stock, 'datetimeutc' => $datetimeutc]));
    }

    /**
     * @return Transaction[]
     * @psalm-return Transaction[]
     */
    public function findBuyTransactionsByAccountWithAmountOutstanding(
        Account $account,
        string $sortOrder,
        string $sortField = 'datetimeutc',
        ?int $limit = 1,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->where('t.type = :type')
            ->andWhere('t.account = :account_id')
            ->andWhere('t.amount_outstanding > 0')
            ->setParameter('type', Transaction::TYPE_BUY)
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->orderBy('t.'.$this->sortFieldToString($sortField), $this->sortOrderToString($sortOrder));
        if (null !== $limit) {
            $qb->setFirstResult($offset)->setMaxResults($limit);
        }
        /** @var Transaction[] */
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Transaction[]
     * @psalm-return Transaction[]
     */
    public function findBuyTransForAccountAndStockWithAmountOutstandingBeforeDate(Account $account, Stock $stock, \DateTime $date): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.type = :type')
            ->andWhere('t.account = :account_id')
            ->andWhere('t.stock = :stock_code')
            ->andWhere('t.amount_outstanding > 0')
            ->andWhere('t.datetimeutc < :datetimeutc')
            ->setParameter('type', Transaction::TYPE_BUY)
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_code', $stock->getId())
            ->setParameter('datetimeutc', $date->format('Y-m-d H:i:s'))
            ->orderBy('t.datetimeutc', 'ASC');
        /** @var Transaction[] */
        return $qb->getQuery()->getResult();
    }

    private function sortFieldToString(string $sortField): string
    {
        return ('amount' === $sortField ? 'amount' : 'datetimeutc');
    }

    private function sortOrderToString(string $sortOrder): string
    {
        return ('ASC' === $sortOrder ? 'ASC' : 'DESC');
    }
}
