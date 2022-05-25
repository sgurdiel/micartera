<?php

namespace xVer\MiCartera\Domain\AccountingMovement;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Transaction\Transaction;

class AccountingMovement implements EntityInterface
{
    public function __construct(private Transaction $buyTransaction, private Transaction $sellTransaction, private int $amount)
    {
        $this->validAmount();
        if (
            Transaction::TYPE_BUY !== $buyTransaction->getType()
            || Transaction::TYPE_SELL !== $sellTransaction->getType()
        ) {
            throw new DomainException(
                new TranslationVO(
                    'transactionAssertType',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
        if (false === $buyTransaction->getStock()->sameId($sellTransaction->getStock())) {
            throw new DomainException(
                new TranslationVO(
                    'transactionAssertStock',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
        if ($buyTransaction->getDateTimeUtc() >= $sellTransaction->getDateTimeUtc()) {
            throw new DomainException(
                new TranslationVO(
                    'accountingMovementAssertDateTime',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
    }

    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof AccountingMovement) {
            throw new \InvalidArgumentException();
        }
        return (
            $this->buyTransaction->sameId($otherEntity->getBuyTransaction())
            && $this->sellTransaction->sameId($otherEntity->getsellTransaction())
        );
    }

    public function getBuyTransaction(): Transaction
    {
        return $this->buyTransaction;
    }

    public function getSellTransaction(): Transaction
    {
        return $this->sellTransaction;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;
        $this->validAmount();
        return $this;
    }

    private function validAmount(): void
    {
        if (
            0 >= $this->amount
            || 99999 < $this->amount
        ) {
            throw new DomainException(
                new TranslationVO(
                    'numberBetween',
                    ['minimum' => '1', 'maximum' => '99999'],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'amount'
            );
        }
    }
}
