<?php

namespace xVer\MiCartera\Infrastructure\Stock;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

trait StockRepositoryTrait
{
    public function add(Stock $stock): Stock
    {
        $this->createConstraints($stock);
        $this->emPersist($stock);
        $this->emFlush();

        return $stock;
    }

    public function update(Stock $stock): void
    {
        $this->emPersist($stock);
        $this->emFlush();
    }

    public function remove(Stock $stock, TransactionRepositoryInterface $transRepo): void
    {
        $this->removeConstraints($stock, $transRepo);
        $this->emRemove($stock);
        $this->emFlush();
    }

    public function createConstraints(Stock $stock): void
    {
        if (null !== $this->findById($stock->getId())) {
            throw new DomainException(
                new TranslationVO(
                    'stockExists',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'code'
            );
        }
    }

    public function removeConstraints(Stock $stock, TransactionRepositoryInterface $transRepo): void
    {
        $stock = $this->findByIdOrThrowException($stock->getId());
        if (!empty($transRepo->findByStockId($stock))) {
            throw new DomainException(
                new TranslationVO(
                    'stockHasTransactions',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'code'
            );
        }
    }

    public function findByIdOrThrowException(string $id): Stock
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
