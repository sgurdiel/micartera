<?php

namespace xVer\MiCartera\Domain\Account;

use Symfony\Component\Uid\Uuid;
use xVer\MiCartera\Domain\Account\Account;
use xVer\Symfony\Bundle\BaseAppBundle\Domain\Account\AccountRepositoryInterface as BaseAccountRepositoryInterface;

interface AccountRepositoryInterface extends BaseAccountRepositoryInterface
{
    public function persist(Account $account): Account;

    public function findById(Uuid $id): ?Account;

    public function findByIdentifierOrThrowException(string $identifier): Account;
}
