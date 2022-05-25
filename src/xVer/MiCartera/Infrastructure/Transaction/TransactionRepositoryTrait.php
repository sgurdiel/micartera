<?php

namespace xVer\MiCartera\Infrastructure\Transaction;

use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInterface;

trait TransactionRepositoryTrait
{
    public function add(Transaction $transaction, AccountingMovementRepositoryInterface $accountingMovementRepo): Transaction
    {
        $this->beginTransaction();
        try {
            $this->createConstraints($transaction, $accountingMovementRepo);
        } catch (DomainException $th) {
            $this->rollback();
            throw $th;
        }
        $this->emPersist($transaction);
        $this->emFlush();
        $accountingMovementRepo->emFlush();
        $this->commit();

        return $transaction;
    }

    public function update(Transaction $transaction): void
    {
        $this->emPersist($transaction);
        $this->emFlush();
    }

    public function remove(Transaction $transaction, AccountingMovementRepositoryInterface $accountingMovementRepo): void
    {
        $this->beginTransaction();
        try {
            $this->removeConstraints($transaction, $accountingMovementRepo);
        } catch (DomainException $th) {
            $this->rollback();
            throw $th;
        }
        $this->emRemove($transaction);
        $this->emFlush();
        $accountingMovementRepo->emFlush();
        $this->commit();
    }

    public function updateOutstandingAmount(AccountingMovement $accountingMovement, bool $increase): void
    {
        $accountingMovement->getBuyTransaction()->setAmountOutstanding($accountingMovement, $increase);
        $this->emPersist($accountingMovement->getBuyTransaction());
    }

    protected function createConstraints(Transaction $transaction, AccountingMovementRepositoryInterface $repoAccountingMovement): void
    {
        if (
            false === $this->assertNoTransWithSameAccountStockOnDateTime(
                $transaction->getAccount(),
                $transaction->getStock(),
                $transaction->getDateTimeUtc()
            )
        ) {
            throw new DomainException(
                new TranslationVO(
                    'transExistsOnDateTime',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
        AccountingMovementFifoContract::apply($repoAccountingMovement, $this, $transaction, AccountingMovementFifoContract::CREATE);
    }

    protected function removeConstraints(Transaction $transaction, AccountingMovementRepositoryInterface $repoAccountingMovement): void
    {
        // Refresh object to ensure constraints are applied using up to date data
        $transaction = $this->findByIdOrThrowException($transaction->getId());
        if (false === $this->assertBuyTransAmountEqualsAmountOutstanding($transaction)) {
            throw new DomainException(
                new TranslationVO(
                    'transBuyCannotBeRemovedWithoutFullAmountOutstanding',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
        AccountingMovementFifoContract::apply($repoAccountingMovement, $this, $transaction, AccountingMovementFifoContract::REMOVE);
    }

    protected function assertBuyTransAmountEqualsAmountOutstanding(Transaction $transaction): bool
    {
        return (
            Transaction::TYPE_SELL === $transaction->getType()
            || $transaction->getAmount() === $transaction->getAmountOutstanding()
        );
    }

    public function findByIdOrThrowException(Uuid $id): Transaction
    {
        if (null === ($object = $this->findById($id))) {
            throw new DomainException(
                new TranslationVO(
                    'expectedPersistedObjectNotFound',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                )
            );
        }
        return $object;
    }
}
