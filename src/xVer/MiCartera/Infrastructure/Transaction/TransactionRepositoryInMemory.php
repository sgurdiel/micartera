<?php

namespace xVer\MiCartera\Infrastructure\Transaction;

use xVer\MiCartera\Domain\Transaction\Transaction;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryTrait;

class TransactionRepositoryInMemory extends PersistanceInMemory implements TransactionRepositoryInterface
{
    use TransactionRepositoryTrait;

    public function findById(Uuid $uuid): ?Transaction
    {
        /** @var Transaction $persistedTransaction */
        foreach ($this->getPersistedObjects() as $persistedTransaction) {
            if ($persistedTransaction->getId()->equals($uuid)) {
                return $persistedTransaction;
            }
        }
        return null;
    }

    /**
     * @return Transaction[]
     */
    public function findByStockId(Stock $stock, int $limit = 1, int $offset = 0): array
    {
        $returnTransactions = [];
        $currentOffset = 0;
        $itemsFound = 0;
        /** @var Transaction $persistedTransaction */
        foreach ($this->getPersistedObjects() as $persistedTransaction) {
            if (
                $currentOffset >= $offset
                && $persistedTransaction->getStock()->sameId($stock)
            ) {
                $returnTransactions[] = $persistedTransaction;
                $itemsFound++;
            }
            $currentOffset++;
            if ($itemsFound >= $limit) {
                break;
            }
        }
        return $returnTransactions;
    }

    /**
     * @return Transaction[]
     */
    public function findByAccount(Account $account, ?int $limit, int $offset, ?string $sortField, ?string $sortOrder): array
    {
        if (null === $sortField || null === $sortOrder) {
            $persistedTransactions = $this->getPersistedObjects();
        } else {
            $persistedTransactions = $this->sort($sortField, $sortOrder);
        }
        $returnTransactions = [];
        $currenOffset = 0;
        $itemsFound = 0;
        /** @var Transaction[] $persistedTransactions */
        foreach ($persistedTransactions as $persistedTransaction) {
            if (
                $currenOffset >= $offset
                && $persistedTransaction->getAccount()->sameId($account)
            ) {
                $returnTransactions[] = $persistedTransaction;
                $itemsFound++;
            }
            if ($limit && $itemsFound >= $limit) {
                break;
            }
        }
        return $returnTransactions;
    }

    protected function assertNoTransWithSameAccountStockOnDateTime(Account $account, Stock $stock, \DateTime $datetimeutc): bool
    {
        /** @var Transaction $persistedTransaction */
        foreach ($this->getPersistedObjects() as $persistedTransaction) {
            if (
                $persistedTransaction->getAccount()->getId()->equals($account->getId())
                && $persistedTransaction->getStock()->sameId($stock)
                && $datetimeutc == $persistedTransaction->getDateTimeUtc()
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return Transaction[]
     */
    private function sort(string $sortField, string $sortDir): array
    {
        if ('amount' === $sortField) {
            $return = $this->sortByAmount($sortDir);
        } else {
            $return = $this->sortByDateTimeUtc($sortDir);
        }
        return $return;
    }

    /**
     * @return Transaction[]
     */
    private function sortByAmount(string $sortOrder): array
    {
        /** @var Transaction[] $persistedTransactions */
        $persistedTransactions = $this->getPersistedObjects();
        usort($persistedTransactions, function (Transaction $a, Transaction $b) use ($sortOrder) {
            return (
                'ASC' === $sortOrder
                ? $a->getAmount() <=> $b->getAmount()
                : $b->getAmount() <=> $a->getAmount()
            );
        });
        return $persistedTransactions;
    }

    /**
     * @return Transaction[]
     */
    private function sortByDateTimeUtc(string $sortOrder): array
    {
        /** @var Transaction[] $persistedTransactions */
        $persistedTransactions = $this->getPersistedObjects();
        usort($persistedTransactions, function (Transaction $a, Transaction $b) use ($sortOrder) {
            return (
                'ASC' === $sortOrder
                ? $a->getDateTimeUtc() <=> $b->getDateTimeUtc()
                : $b->getDateTimeUtc() <=> $a->getDateTimeUtc()
            );
        });
        return $persistedTransactions;
    }

    /** @return Transaction[] */
    public function findBuyTransactionsByAccountWithAmountOutstanding(
        Account $account,
        string $sortOrder,
        string $sortField = 'datetimeutc',
        ?int $limit = 1,
        int $offset = 0
    ): array {
        $persistedTransactions = $this->sort($sortField, $sortOrder);
        $returnTransactions = [];
        $currentOffset = 0;
        $itemsFound = 0;
        foreach ($persistedTransactions as $persistedTransaction) {
            if (
                $currentOffset >= $offset
                && Transaction::TYPE_BUY === $persistedTransaction->getType()
                && $persistedTransaction->getAccount()->sameId($account)
                && 0 < $persistedTransaction->getAmountOutstanding()
            ) {
                $returnTransactions[] = $persistedTransaction;
                $itemsFound++;
            }
            $currentOffset++;
            if ($limit && $itemsFound >= $limit) {
                break;
            }
        }

        return $returnTransactions;
    }

    /**
     * @return Transaction[]
     */
    public function findBuyTransForAccountAndStockWithAmountOutstandingBeforeDate(Account $account, Stock $stock, \DateTime $date): array
    {
        $persistedTransactions = $this->sort('datetimeutc', 'ASC');
        $returnTransactions = [];
        foreach ($persistedTransactions as $persistedTransaction) {
            if (
                Transaction::TYPE_BUY === $persistedTransaction->getType()
                && $persistedTransaction->getStock()->sameId($stock)
                && $persistedTransaction->getAccount()->sameId($account)
                && $date > $persistedTransaction->getDateTimeUtc()
                && 0 < $persistedTransaction->getAmountOutstanding()
            ) {
                $returnTransactions[] = $persistedTransaction;
            }
        }

        return $returnTransactions;
    }
}
