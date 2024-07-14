<?php

namespace xVer\MiCartera\Infrastructure\Currency;

use Doctrine\Persistence\ManagerRegistry;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Currency\CurrenciesCollection;
use xVer\MiCartera\Domain\Currency\CurrencyRepositoryInterface;
use xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine;

/**
 * @template-extends EntityObjectRepositoryDoctrine<Currency>
 */
class CurrencyRepositoryDoctrine extends EntityObjectRepositoryDoctrine implements CurrencyRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Currency::class);
    }

    public function persist(Currency $currency): Currency
    {
        $this->getEntityManager()->persist($currency);
        return $currency;
    }

    /**
     * @psalm-return Currency|null
     */
    public function findById(string $iso3): ?Currency
    {
        return $this->find($iso3);
    }

    public function findByIdOrThrowException(string $iso3): Currency
    {
        if (null === ($object = $this->findById($iso3))) {
            throw new DomainException(
                new TranslationVO(
                    'expectedPersistedObjectNotFound',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'currency'
            );
        }
        return $object;
    }

    public function all(): CurrenciesCollection
    {
        return new CurrenciesCollection($this->findAll());
    }
}
