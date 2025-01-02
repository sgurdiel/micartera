<?php

namespace xVer\MiCartera\Infrastructure\Exchange;

use Doctrine\Persistence\ManagerRegistry;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Exchange\Exchange;
use xVer\MiCartera\Domain\Exchange\ExchangeRepositoryInterface;
use xVer\MiCartera\Domain\Exchange\ExchangesCollection;
use xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine;

/**
 * @template-extends EntityObjectRepositoryDoctrine<Exchange>
 */
class ExchangeRepositoryDoctrine extends EntityObjectRepositoryDoctrine implements ExchangeRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Exchange::class);
    }

    public function persist(Exchange $exchange): Exchange
    {
        $this->getEntityManager()->persist($exchange);
        return $exchange;
    }

    /**
     * @psalm-return Exchange|null
     */
    public function findById(string $code): ?Exchange
    {
        return $this->find($code);
    }

    public function findByIdOrThrowException(string $code): Exchange
    {
        $object = $this->findById($code);
        if (null === ($object)) {
            throw new DomainException(
                new TranslationVO(
                    'expectedPersistedObjectNotFound',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'Exchange'
            );
        }
        return $object;
    }

    public function all(): ExchangesCollection
    {
        return new ExchangesCollection($this->findAll());
    }
}
