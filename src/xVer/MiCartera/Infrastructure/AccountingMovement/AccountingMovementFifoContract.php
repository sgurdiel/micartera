<?php

namespace xVer\MiCartera\Infrastructure\AccountingMovement;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

/**
 * Accounting Movements must respect FIFO (First In First Out).
 */
final class AccountingMovementFifoContract
{
    public const CREATE = 0;
    public const REMOVE = 1;

    public function __construct(
        private AccountingMovementRepositoryInterface $repoAccountingMovement,
        private TransactionRepositoryInterface $repoTrans,
        private Transaction $transaction,
        private int $operationType
    ) {
    }

    public static function apply(
        AccountingMovementRepositoryInterface $repoAccountingMovement,
        TransactionRepositoryInterface $repoTrans,
        Transaction $transaction,
        int $operationType
    ): void {
        $contractInstance = new self($repoAccountingMovement, $repoTrans, $transaction, $operationType);
        if (Transaction::TYPE_BUY === $contractInstance->transaction->getType()) {
            $contractInstance->onBuyTransactionCreation();
        } elseif (Transaction::TYPE_SELL == $contractInstance->transaction->getType()) {
            if (self::CREATE === $operationType) {
                $contractInstance->onSellTransactionCreation();
            } elseif (self::REMOVE === $operationType) {
                $contractInstance->onSellTransactionRemoval();
            }
        }
    }

    private function onBuyTransactionCreation(): bool
    {
        // Retrieve Accounting Movements sorted by buy transaction date ascending then by sell transaction date ascending
        $accountingMovements = $this->repoAccountingMovement->findByAccountStockBuyDateAfter(
            $this->transaction->getAccount(),
            $this->transaction->getStock(),
            $this->transaction->getDateTimeUtc()
        );
        $buyTransWithAmountOutstanding = [$this->transaction];
        $buyTransWithAmountOutstandingClone = $this->extractBuyTransWithAmountOutstandingsRestored($accountingMovements, $buyTransWithAmountOutstanding);
        $newAccountingMovements = $this->prepareNewAccountingMovements($accountingMovements, $buyTransWithAmountOutstanding, $buyTransWithAmountOutstandingClone);
        $this->mergeAccountingMovementsAndPersist($accountingMovements, $newAccountingMovements);

        return true;
    }

    private function onSellTransactionCreation(): bool
    {
        // Retrieve Accounting Movements sorted by buy transaction date ascending then by sell transaction date ascending
        $accountingMovements = $this->repoAccountingMovement->findByAccountStockSellDateAfter(
            $this->transaction->getAccount(),
            $this->transaction->getStock(),
            $this->transaction->getDateTimeUtc()
        );

        $countAccountingMovements = count($accountingMovements);
        $date = (0 < $countAccountingMovements)
            ? $accountingMovements[$countAccountingMovements-1]->getSellTransaction()->getDateTimeUtc()
            : $this->transaction->getDateTimeUtc();
        $buyTransWithAmountOutstanding = $this->repoTrans->findBuyTransForAccountAndStockWithAmountOutstandingBeforeDate(
            $this->transaction->getAccount(),
            $this->transaction->getStock(),
            $date
        );
        $buyTransWithAmountOutstandingClone = $this->extractBuyTransWithAmountOutstandingsRestored($accountingMovements, $buyTransWithAmountOutstanding);
        $newAccountingMovements = $this->prepareNewAccountingMovements($accountingMovements, $buyTransWithAmountOutstanding, $buyTransWithAmountOutstandingClone);
        $this->mergeAccountingMovementsAndPersist($accountingMovements, $newAccountingMovements);

        return true;
    }

    private function onSellTransactionRemoval(): bool
    {
        // Retrieve Accounting Movements sorted by buy transaction date ascending then by sell transaction date ascending
        $accountingMovements = $this->repoAccountingMovement->findByAccountStockSellDateAtOrAfter(
            $this->transaction->getAccount(),
            $this->transaction->getStock(),
            $this->transaction->getDateTimeUtc()
        );
        $buyTransWithAmountOutstanding = [];
        $buyTransWithAmountOutstandingClone = $this->extractBuyTransWithAmountOutstandingsRestored($accountingMovements, $buyTransWithAmountOutstanding);
        $newAccountingMovements = $this->prepareNewAccountingMovements($accountingMovements, $buyTransWithAmountOutstanding, $buyTransWithAmountOutstandingClone);
        $this->mergeAccountingMovementsAndPersist($accountingMovements, $newAccountingMovements);

        return true;
    }

    /**
     * @param AccountingMovement[] $oldAccountingMovements
     * @param AccountingMovement[] $newAccountingMovements
     */
    private function mergeAccountingMovementsAndPersist(array $oldAccountingMovements, array $newAccountingMovements): void
    {
        $this->updateOrCreateAccountingMovements($oldAccountingMovements, $newAccountingMovements);
        $this->removeDiscardedAccountingMovements($oldAccountingMovements, $newAccountingMovements);
    }

    /**
     * @param AccountingMovement[] $accountingMovements
     * @param Transaction[] $buyTransWithAmountOutstanding
     * @param Transaction[] $buyTransWithAmountOutstandingClone
     * @return AccountingMovement[]
     */
    private function prepareNewAccountingMovements(
        array $accountingMovements,
        array $buyTransWithAmountOutstanding,
        array $buyTransWithAmountOutstandingClone
    ): array {
        /** @var AccountingMovement[] */
        $newAccountingMovements = [];
        if (self::CREATE === $this->operationType && Transaction::TYPE_SELL === $this->transaction->getType()) {
            $this->prepareNewAccountingMovementsForNewSellTransaction(
                $buyTransWithAmountOutstanding,
                $buyTransWithAmountOutstandingClone,
                $newAccountingMovements
            );
        }
        $this->prepareNewAccountingMovementsForExistingSellTransactions(
            $accountingMovements,
            $buyTransWithAmountOutstanding,
            $buyTransWithAmountOutstandingClone,
            $newAccountingMovements
        );
        return $newAccountingMovements;
    }

    /**
     * @param AccountingMovement[] $accountingMovements
     * @param Transaction[] $buyTransWithAmountOutstanding
     * @return Transaction[]
     */
    private function extractBuyTransWithAmountOutstandingsRestored(array $accountingMovements, array &$buyTransWithAmountOutstanding): array
    {
        /** @var Transaction[] */
        $buyTransWithAmountOutstandingClone = [];
        foreach ($buyTransWithAmountOutstanding as $buyTransaction) {
            $buyTransWithAmountOutstandingClone[] = clone $buyTransaction;
        }
        foreach ($accountingMovements as $accountingMovement) {
            $found = false;
            foreach ($buyTransWithAmountOutstanding as $keyBuy => $buyTransaction) {
                if ($accountingMovement->getBuyTransaction()->sameId($buyTransaction)) {
                    $buyTransWithAmountOutstandingClone[$keyBuy]->setAmountOutstanding($accountingMovement, true);
                    $found = true;
                    break;
                }
            }
            if (false === $found) {
                $aux = clone $accountingMovement->getBuyTransaction();
                $aux->setAmountOutstanding($accountingMovement, true);
                $buyTransWithAmountOutstandingClone[] = $aux;
                $buyTransWithAmountOutstanding[] = $accountingMovement->getBuyTransaction();
            }
        }
        usort($buyTransWithAmountOutstanding, function (Transaction $a, Transaction $b) {
            return $a->getDateTimeUtc() <=> $b->getDateTimeUtc();
        });
        usort($buyTransWithAmountOutstandingClone, function (Transaction $a, Transaction $b) {
            return $a->getDateTimeUtc() <=> $b->getDateTimeUtc();
        });
        return $buyTransWithAmountOutstandingClone;
    }

    /**
     * @param AccountingMovement[] $oldAccountingMovements
     * @param AccountingMovement[] $newAccountingMovements
     */
    private function updateOrCreateAccountingMovements(array $oldAccountingMovements, array $newAccountingMovements): void
    {
        foreach ($newAccountingMovements as $newAccountingMovement) {
            $found = false;
            foreach ($oldAccountingMovements as $oldAccountingMovement) {
                if ($newAccountingMovement->sameId($oldAccountingMovement)) {
                    $this->repoAccountingMovement->updateAmount(
                        $oldAccountingMovement,
                        $newAccountingMovement->getAmount(),
                        $this->repoTrans
                    );
                    $found = true;
                    break;
                }
            }
            if (false === $found) {
                $this->repoAccountingMovement->add($newAccountingMovement, $this->repoTrans);
            }
        }
    }

    /**
     * @param AccountingMovement[] $oldAccountingMovements
     * @param AccountingMovement[] $newAccountingMovements
     */
    private function removeDiscardedAccountingMovements(array $oldAccountingMovements, array $newAccountingMovements): void
    {
        foreach ($oldAccountingMovements as $oldAccountingMovement) {
            $found = false;
            foreach ($newAccountingMovements as $newAccountingMovement) {
                if ($oldAccountingMovement->sameId($newAccountingMovement)) {
                    $found = true;
                    break;
                }
            }
            if (false === $found) {
                $this->repoAccountingMovement->remove($oldAccountingMovement, $this->repoTrans);
            }
        }
    }

    /**
     * @param Transaction[] $buyTransWithAmountOutstanding
     * @param Transaction[] $buyTransWithAmountOutstandingClone
     * @param AccountingMovement[] $newAccountingMovements
     */
    private function prepareNewAccountingMovementsForNewSellTransaction(
        array $buyTransWithAmountOutstanding,
        array $buyTransWithAmountOutstandingClone,
        array &$newAccountingMovements
    ): void {
        $remainderAmount = $this->transaction->getAmount();
        foreach ($buyTransWithAmountOutstandingClone as $keyBuy => $buyTransaction) {
            if ($buyTransaction->getDateTimeUtc() > $this->transaction->getDateTimeUtc()) {
                continue;
            }
            $buyTransactionAmountOutstanding = $buyTransaction->getAmountOutstanding();
            if (0 < $buyTransactionAmountOutstanding) {
                $accountingMovementAmount = ($remainderAmount > $buyTransactionAmountOutstanding ? $buyTransactionAmountOutstanding : $remainderAmount);
                $newAccountingMovements[] = $newAccountingMovement = new AccountingMovement($buyTransWithAmountOutstanding[$keyBuy], $this->transaction, $accountingMovementAmount);
                $buyTransWithAmountOutstandingClone[$keyBuy]->setAmountOutstanding($newAccountingMovement, false);
                $remainderAmount -= $accountingMovementAmount;
            }
            if (0 === $remainderAmount) {
                break;
            }
        }
        if (0 !== $remainderAmount) {
            throw new DomainException(new TranslationVO('transNotPassFifoSpec', [], TranslationVO::DOMAIN_VALIDATORS));
        }
    }

    /**
     * @param AccountingMovement[] $accountingMovements
     * @param Transaction[] $buyTransWithAmountOutstanding
     * @param Transaction[] $buyTransWithAmountOutstandingClone
     * @param AccountingMovement[] $newAccountingMovements
     */
    private function prepareNewAccountingMovementsForExistingSellTransactions(
        array $accountingMovements,
        array $buyTransWithAmountOutstanding,
        array $buyTransWithAmountOutstandingClone,
        array &$newAccountingMovements
    ): void {
        foreach ($accountingMovements as $accountingMovement) {
            if (
                self::REMOVE === $this->operationType
                && Transaction::TYPE_SELL === $this->transaction->getType()
                && $accountingMovement->getSellTransaction()->sameId($this->transaction)
            ) {
                continue;
            }
            $remainderAmount = $accountingMovement->getAmount();
            foreach ($buyTransWithAmountOutstandingClone as $keyBuy => $buyTransactionClone) {
                if ($buyTransactionClone->getDateTimeUtc() > $accountingMovement->getSellTransaction()->getDateTimeUtc()) {
                    continue;
                }
                $buyTransactionAmountOutstanding = $buyTransactionClone->getAmountOutstanding();
                if (0 < $buyTransactionAmountOutstanding) {
                    $remainderAmount -= $this->prepareNewAccountingMovementsForExistingSellTransactionsIsNewEntryOrExisting(
                        $newAccountingMovements,
                        $buyTransWithAmountOutstanding[$keyBuy],
                        $buyTransactionClone,
                        $accountingMovement->getSellTransaction(),
                        $remainderAmount,
                        $buyTransactionAmountOutstanding
                    );
                }
                if (0 === $remainderAmount) {
                    break;
                }
            }
            if (0 !== $remainderAmount) {
                throw new DomainException(new TranslationVO('transNotPassFifoSpec', [], TranslationVO::DOMAIN_VALIDATORS));
            }
        }
    }

    /**
     * @param AccountingMovement[] $newAccountingMovements
     * @param Transaction $buyTransaction
     * @param Transaction $buyTransactionClone
     * @param Transaction $accountingMovementSellTransaction
     * @param int $remainderAmount
     * @param int $buyTransactionAmountOutstanding
     */
    private function prepareNewAccountingMovementsForExistingSellTransactionsIsNewEntryOrExisting(
        array &$newAccountingMovements,
        Transaction $buyTransaction,
        Transaction $buyTransactionClone,
        Transaction $accountingMovementSellTransaction,
        int $remainderAmount,
        int $buyTransactionAmountOutstanding
    ): int {
        $accountingMovementAmount = ($remainderAmount > $buyTransactionAmountOutstanding ? $buyTransactionAmountOutstanding : $remainderAmount);
        $foundNew = false;
        foreach ($newAccountingMovements as $key => $newAccountingMovement) {
            if (
                $newAccountingMovement->getBuyTransaction()->getId()->equals($buyTransactionClone->getId())
                && $newAccountingMovement->getSellTransaction()->getId()->equals($accountingMovementSellTransaction->getId())
            ) {
                $buyTransactionClone->setAmountOutstanding($newAccountingMovement, true);
                $newAccountingMovements[$key] = new AccountingMovement(
                    $buyTransaction,
                    $accountingMovementSellTransaction,
                    ($newAccountingMovement->getAmount() + $accountingMovementAmount)
                );
                $buyTransactionClone->setAmountOutstanding($newAccountingMovements[$key], false);
                $foundNew = true;
                break;
            }
        }
        if (false === $foundNew) {
            $newAccountingMovements[] = $newAccountingMovement = new AccountingMovement(
                $buyTransaction,
                $accountingMovementSellTransaction,
                $accountingMovementAmount
            );
            $buyTransactionClone->setAmountOutstanding($newAccountingMovement, false);
        }
        return $accountingMovementAmount;
    }
}
