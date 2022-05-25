<?php

namespace xVer\MiCartera\Infrastructure\Account;

use Doctrine\Persistence\ManagerRegistry;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryTrait;
use xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine;

/**
 * @template T
 * @template-extends PersistanceDoctrine<Account>
 */
class AccountRepositoryDoctrine extends PersistanceDoctrine implements AccountRepositoryInterface
{
    use AccountRepositoryTrait;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Account::class);
    }

    public function findByIdentifier(string $identifier): ?Account
    {
        return $this->findOneBy(['email' => $identifier]);
    }
}
