<?php

namespace xVer\MiCartera\Infrastructure\Account;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine;

/**
 * @template-extends EntityObjectRepositoryDoctrine<Account>
 */
class AccountRepositoryDoctrine extends EntityObjectRepositoryDoctrine implements AccountRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Account::class);
    }

    public function persist(Account $account): Account
    {
        $this->getEntityManager()->persist($account);
        return $account;
    }

    public function findByIdentifier(string $identifier): ?Account
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a, c')
            ->innerJoin('a.currency', 'c')
            ->where('a.email = :email')
            ->setParameter('email', $identifier, 'string');
        $query = $qb->getQuery();
        return $query->getOneOrNullResult($query::HYDRATE_OBJECT);
    }

    /**
     * @psalm-return Account|null
     */
    public function findById(Uuid $id): ?Account
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findByIdentifierOrThrowException(string $identifier): Account
    {
        if (null === ($object = $this->findByIdentifier($identifier))) {
            throw new DomainException(
                new TranslationVO(
                    'expectedPersistedObjectNotFound',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'account'
            );
        }
        return $object;
    }
}
