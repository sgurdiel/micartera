<?php

namespace xVer\MiCartera\Infrastructure\Transaction;

use xVer\MiCartera\Domain\Transaction\Transaction;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Infrastructure\RepositoryInterface;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInterface;

interface TransactionRepositoryInterface extends RepositoryInterface
{
    public function add(Transaction $transaction, AccountingMovementRepositoryInterface $accountingMovementRepo): Transaction;

    public function update(Transaction $transaction): void;

    public function remove(Transaction $transaction, AccountingMovementRepositoryInterface $accountingMovementRepo): void;

    public function updateOutstandingAmount(AccountingMovement $accountingMovement, bool $increase): void;

    public function findById(Uuid $uuid): ?Transaction;

    /** @return Transaction[] */
    public function findByStockId(Stock $stock, int $limit = 1, int $offset = 0): array;

    /** @return Transaction[] */
    public function findByAccount(Account $account, ?int $limit, int $offset, ?string $sortField, ?string $sortOrder): array;

    /** @return Transaction[] */
    public function findBuyTransForAccountAndStockWithAmountOutstandingBeforeDate(Account $account, Stock $stock, \DateTime $date): array;

    /** @return Transaction[] */
    public function findBuyTransactionsByAccountWithAmountOutstanding(
        Account $account,
        string $sortOrder,
        string $sortField = 'datetimeutc',
        ?int $limit = 1,
        int $offset = 0
    ): array;

    public function findByIdOrThrowException(Uuid $id): Transaction;
}
