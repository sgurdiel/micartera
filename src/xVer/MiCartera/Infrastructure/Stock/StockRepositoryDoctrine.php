<?php

namespace xVer\MiCartera\Infrastructure\Stock;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryInterface;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryTrait;

/**
 * @template T
 * @template-extends PersistanceDoctrine<Stock>
 */
class StockRepositoryDoctrine extends PersistanceDoctrine implements StockRepositoryInterface
{
    use StockRepositoryTrait;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Stock::class);
    }

    /**
     * @psalm-return Stock|null
     */
    public function findById(string $code): ?Stock
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * @return Stock[]
     * @psalm-return Stock[]
     */
    public function findByCurrencySorted(Currency $currency, int $limit, int $offset, string $sortField = 'code', string $sortDir = 'ASC'): array
    {
        return $this->findBy(
            ['currency' => $currency->getIso3()],
            [$sortField => $sortDir],
            $limit,
            $offset
        );
    }

    public function queryBuilderForTransactionForm(Currency $currency): QueryBuilder
    {
        return $this->createQueryBuilder('s')->where('s.currency = :currency')->setParameter('currency', $currency)->orderBy('s.code', 'ASC');
    }
}
