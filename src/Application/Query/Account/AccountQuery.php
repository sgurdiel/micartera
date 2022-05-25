<?php

namespace xVer\MiCartera\Application\Query\Account;

use xVer\Bundle\DomainBundle\Application\AbstractApplication;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\Symfony\Bundle\BaseAppBundle\Application\Query\Account\AccountQueryInterface;

class AccountQuery extends AbstractApplication implements AccountQueryInterface
{
    public function byIdentifier(string $identifier): Account
    {
        return $this->repoLoader->load(AccountRepositoryInterface::class)
        ->findByIdentifierOrThrowException($identifier);
    }
}
