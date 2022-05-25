<?php

namespace xVer\MiCartera\Infrastructure\AccountingMovement;

use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Infrastructure\RepositoryInterface;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

interface AccountingMovementRepositoryInterface extends RepositoryInterface
{
    public function add(AccountingMovement $accountingMovement, TransactionRepositoryInterface $repoTrans): AccountingMovement;

    public function remove(AccountingMovement $accountingMovement, TransactionRepositoryInterface $repoTrans): void;

    /** @return AccountingMovement[] */
    public function findBySellTransactionId(Uuid $sellUuid): array;

    /** @return AccountingMovement */
    public function findByBuyAndSellTransactionIds(Uuid $buyUuid, Uuid $sellUuid): ?AccountingMovement;

    /** @return AccountingMovement[] */
    public function findByAccountAndYear(Account $account, int $year, int $offset, ?int $limit): array;

    public function findYearOfOldestMovementByAccount(Account $account): int;

    /** @psalm-return non-empty-array<string|null> */
    public function findTotalPurchaseAndSaleByAccount(Account $account): array;

    /** @return AccountingMovement[] */
    public function findByAccountStockBuyDateAfter(Account $account, Stock $stock, \DateTime $dateTime): array;

    /** @return AccountingMovement[] */
    public function findByAccountStockSellDateAfter(Account $account, Stock $stock, \DateTime $dateTime): array;

    /** @return AccountingMovement[] */
    public function findByAccountStockSellDateAtOrAfter(Account $account, Stock $stock, \DateTime $dateTime): array;

    public function updateAmount(
        AccountingMovement $accountingMovement,
        int $newAmount,
        TransactionRepositoryInterface $repoTrans
    ): void;

    public function findByIdOrThrowException(AccountingMovement $id): AccountingMovement;
}
