<?php

namespace xVer\MiCartera\Infrastructure\AccountingMovement;

use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\MiCartera\Domain\NumberOperation;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInterface;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryTrait;
use xVer\MiCartera\Domain\Stock\Stock;

class AccountingMovementRepositoryInMemory extends PersistanceInMemory implements AccountingMovementRepositoryInterface
{
    use AccountingMovementRepositoryTrait;

    public function findBySellTransactionId(Uuid $sellUuid): array
    {
        $auxAccountingMovements = [];
        /** @var AccountingMovement $persistedAccountingMovement */
        foreach ($this->getPersistedObjects() as $persistedAccountingMovement) {
            if ($persistedAccountingMovement->getSellTransaction()->getId()->equals($sellUuid)) {
                $auxAccountingMovements[] = $persistedAccountingMovement;
            }
        }
        return $auxAccountingMovements;
    }

    public function findByBuyAndSellTransactionIds(Uuid $buyUuid, Uuid $sellUuid): ?AccountingMovement
    {
        $returnAccountingMovement = null;
        /** @var AccountingMovement $persistedAccountingMovement */
        foreach ($this->getPersistedObjects() as $persistedAccountingMovement) {
            if (
                $persistedAccountingMovement->getBuyTransaction()->getId()->equals($buyUuid)
                && $persistedAccountingMovement->getSellTransaction()->getId()->equals($sellUuid)
            ) {
                $returnAccountingMovement = $persistedAccountingMovement;
                break;
            }
        }
        return $returnAccountingMovement;
    }

    /**
     * @return AccountingMovement[]
     */
    public function findByAccountAndYear(Account $account, int $year, int $offset, ?int $limit): array
    {
        $auxAccountingMovements = [];
        $pos = 0;
        $amount = 0;
        /** @var AccountingMovement $persistedAccountingMovement */
        foreach ($this->getPersistedObjects() as $persistedAccountingMovement) {
            $accountingMovementYear = (int) $persistedAccountingMovement->getSellTransaction()->getDateTimeUtc()->setTimezone($account->getTimeZone())->format('Y');
            if (
                $account->sameId($persistedAccountingMovement->getSellTransaction()->getAccount())
                && $year === $accountingMovementYear
            ) {
                if ($pos >= $offset) {
                    $auxAccountingMovements[] = $persistedAccountingMovement;
                    $amount++;
                }
                $pos++;
            }
            if (!is_null($limit) && $amount >= $limit) {
                break;
            }
        }
        return $auxAccountingMovements;
    }

    public function findYearOfOldestMovementByAccount(Account $account): int
    {
        $now = new \DateTime('now', $account->getTimeZone());
        /** @var AccountingMovement $persistedAccountingMovement */
        foreach ($this->getPersistedObjects() as $persistedAccountingMovement) {
            $accountingMovementDate = $persistedAccountingMovement->getSellTransaction()->getDateTimeUtc()->setTimezone($account->getTimeZone());
            if (
                (int) $accountingMovementDate->format('Y') < (int) $now->format('Y')
            ) {
                $now = $accountingMovementDate;
            }
        }
        return (int) $now->format('Y');
    }

    public function findTotalPurchaseAndSaleByAccount(Account $account): array
    {
        $total_buy = '0';
        $total_sell = '0';
        /** @var AccountingMovement $persistedAccountingMovement */
        foreach ($this->getPersistedObjects() as $persistedAccountingMovement) {
            if ($persistedAccountingMovement->getSellTransaction()->getAccount()->sameId($account)) {
                $total_buy = NumberOperation::add(
                    4,
                    $total_buy,
                    NumberOperation::multiply(4, $persistedAccountingMovement->getBuyTransaction()->getPrice()->getValue(), (string) $persistedAccountingMovement->getAmount())
                );
                $total_sell = NumberOperation::add(
                    4,
                    $total_sell,
                    NumberOperation::multiply(4, $persistedAccountingMovement->getSellTransaction()->getPrice()->getValue(), (string) $persistedAccountingMovement->getAmount())
                );
            }
        }
        return [
            "buy" => $total_buy,
            "sell" => $total_sell
        ];
    }

    /**
     * @return AccountingMovement[]
     */
    public function findByAccountStockBuyDateAfter(Account $account, Stock $stock, \DateTime $dateTime): array
    {
        $auxAccountingMovements = [];
        /** @var AccountingMovement $persistedAccountingMovement */
        foreach ($this->getPersistedObjects() as $persistedAccountingMovement) {
            if (
                $account === $persistedAccountingMovement->getSellTransaction()->getAccount()
                && $stock === $persistedAccountingMovement->getSellTransaction()->getStock()
                && $dateTime < $persistedAccountingMovement->getBuyTransaction()->getDateTimeUtc()
            ) {
                $auxAccountingMovements[] = $persistedAccountingMovement;
            }
        }
        return $this->sortByBuyDateAscAndSellDateAsc($auxAccountingMovements);
    }

    /**
     * @return AccountingMovement[]
     */
    public function findByAccountStockSellDateAfter(Account $account, Stock $stock, \DateTime $dateTime): array
    {
        $auxAccountingMovements = [];
        /** @var AccountingMovement $persistedAccountingMovement */
        foreach ($this->getPersistedObjects() as $persistedAccountingMovement) {
            if (
                $account === $persistedAccountingMovement->getSellTransaction()->getAccount()
                && $stock === $persistedAccountingMovement->getSellTransaction()->getStock()
                && $dateTime < $persistedAccountingMovement->getSellTransaction()->getDateTimeUtc()
            ) {
                $auxAccountingMovements[] = $persistedAccountingMovement;
            }
        }
        return $this->sortByBuyDateAscAndSellDateAsc($auxAccountingMovements);
    }

    /**
     * @return AccountingMovement[]
     */
    public function findByAccountStockSellDateAtOrAfter(Account $account, Stock $stock, \DateTime $dateTime): array
    {
        $auxAccountingMovements = [];
        /** @var AccountingMovement $persistedAccountingMovement */
        foreach ($this->getPersistedObjects() as $persistedAccountingMovement) {
            if (
                $account === $persistedAccountingMovement->getSellTransaction()->getAccount()
                && $stock === $persistedAccountingMovement->getSellTransaction()->getStock()
                && $dateTime <= $persistedAccountingMovement->getSellTransaction()->getDateTimeUtc()
            ) {
                $auxAccountingMovements[] = $persistedAccountingMovement;
            }
        }
        return $this->sortByBuyDateAscAndSellDateAsc($auxAccountingMovements);
    }

    /**
     * @param AccountingMovement[] $accountingMovements
     * @return AccountingMovement[]
     */
    private function sortByBuyDateAscAndSellDateAsc(array $accountingMovements): array
    {
        usort($accountingMovements, function (AccountingMovement $a, AccountingMovement  $b) {
            if ($a->getBuyTransaction()->getDateTimeUtc() == $b->getBuyTransaction()->getDateTimeUtc()) {
                return $a->getSellTransaction()->getDateTimeUtc() <=> $b->getSellTransaction()->getDateTimeUtc();
            }
            return $a->getBuyTransaction()->getDateTimeUtc() <=> $b->getBuyTransaction()->getDateTimeUtc();
        });
        return $accountingMovements;
    }
}
