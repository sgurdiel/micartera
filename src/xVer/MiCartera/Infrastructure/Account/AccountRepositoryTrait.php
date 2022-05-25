<?php

namespace xVer\MiCartera\Infrastructure\Account;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInterface;

trait AccountRepositoryTrait
{
    public function add(Account $account, CurrencyRepositoryInterface $repoCurrency): Account
    {
        $this->createConstraints($account, $repoCurrency);
        $this->emPersist($account);
        $this->emFlush();

        return $account;
    }

    public function createConstraints(Account $account, CurrencyRepositoryInterface $repoCurrency): void
    {
        if (null !== $this->findByIdentifier($account->getEmail())) {
            throw new DomainException(
                new TranslationVO(
                    'accountEmailExists',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'email'
            );
        }

        if (null === $repoCurrency->findById($account->getCurrency()->getIso3())) {
            throw new DomainException(
                new TranslationVO(
                    'relatedEntityNotPersisted',
                    ['entity' => 'Currency', 'entity2' => 'Account'],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'currency'
            );
        }
    }
}
