<?php

namespace xVer\MiCartera\Infrastructure\Account;

use xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryTrait;

class AccountRepositoryInMemory extends PersistanceInMemory implements AccountRepositoryInterface
{
    use AccountRepositoryTrait;

    public function findByIdentifier(string $identifier): ?Account
    {
        /** @var Account $account */
        foreach ($this->getPersistedObjects() as $account) {
            if (strtolower($account->getEmail()) === strtolower($identifier)) {
                return $account;
            }
        }
        return null;
    }
}
