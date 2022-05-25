<?php

namespace xVer\MiCartera\Infrastructure\Currency;

use Doctrine\Persistence\ManagerRegistry;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine;

/**
 * @template T
 * @template-extends PersistanceDoctrine<Currency>
 */
class CurrencyRepositoryDoctrine extends PersistanceDoctrine implements CurrencyRepositoryInterface
{
    use CurrencyRepositoryTrait;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Currency::class);
    }

    /**
     * @psalm-return Currency
     */
    public function findById(string $iso3): ?Currency
    {
        return $this->find($iso3);
    }
}
