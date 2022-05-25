<?php

namespace xVer\MiCartera\Infrastructure\AccountingMovement;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

trait AccountingMovementRepositoryTrait
{
    public function add(AccountingMovement $accountingMovement, TransactionRepositoryInterface $repoTrans): AccountingMovement
    {
        $this->emPersist($accountingMovement);
        $repoTrans->updateOutstandingAmount($accountingMovement, false);

        return $accountingMovement;
    }

    public function remove(AccountingMovement $accountingMovement, TransactionRepositoryInterface $repoTrans): void
    {
        $accountingMovement = $this->findByIdOrThrowException($accountingMovement);
        $this->emRemove($accountingMovement);
        $repoTrans->updateOutstandingAmount($accountingMovement, true);
    }

    public function updateAmount(
        AccountingMovement $accountingMovement,
        int $newAmount,
        TransactionRepositoryInterface $repoTrans
    ): void
    {
        $repoTrans->updateOutstandingAmount($accountingMovement, true);
        $accountingMovement->setAmount($newAmount);
        $this->emPersist($accountingMovement);
        $repoTrans->updateOutstandingAmount($accountingMovement, false);
    }

    public function findByIdOrThrowException(AccountingMovement $id): AccountingMovement
    {
        if (null === ($object = $this->findByBuyAndSellTransactionIds($id->getBuyTransaction()->getId(), $id->getSellTransaction()->getId()))) {
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
