<?php

namespace xVer\MiCartera\Infrastructure\Stock;

use Doctrine\Persistence\ManagerRegistry;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Stock\StockRepositoryInterface;
use xVer\MiCartera\Domain\Stock\StocksCollection;
use xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine;

/**
 * @template-extends EntityObjectRepositoryDoctrine<Stock>
 */
class StockRepositoryDoctrine extends EntityObjectRepositoryDoctrine implements StockRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Stock::class);
    }

    public function persist(Stock $stock): Stock
    {
        $this->getEntityManager()->persist($stock);
        return $stock;
    }

    public function remove(Stock $stock): void
    {
        $this->getEntityManager()->remove($stock);
    }

    /**
     * @psalm-return Stock|null
     */
    public function findById(string $code): ?Stock
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findByIdOrThrowException(string $id): Stock
    {
        $object = $this->findById($id);
        if (null === ($object)) {
            throw new DomainException(
                new TranslationVO(
                    'expectedPersistedObjectNotFound',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'stock'
            );
        }
        return $object;
    }

    public function findByCurrency(
        Currency $currency,
        ?int $limit = null,
        int $offset = 0,
        string $sortField = 'code',
        string $sortDir = 'ASC'
    ): StocksCollection {
        return new StocksCollection(
            $this->findBy(
                ['currency' => $currency->getIso3()],
                [$sortField => $sortDir],
                $limit,
                $offset
            )
        );
    }
}
