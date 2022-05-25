<?php

namespace xVer\MiCartera\Application\Query\Stock;

use xVer\Bundle\DomainBundle\Application\AbstractApplication;
use xVer\Bundle\DomainBundle\Application\Query\EntityObjectsCollectionQueryResponse;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Stock\StockRepositoryInterface;

class StockQuery extends AbstractApplication
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    public string $currencySymbol;

    /**
     * @return EntityObjectsCollectionQueryResponse<Stock>
     */
    public function byAccountsCurrency(
        string $accountIdentifier,
        int $limit = 0,
        int $page = 0,
        string $sortField = 'code',
        string $sortDir = 'ASC'
    ): EntityObjectsCollectionQueryResponse {
        $currency = $this->repoLoader->load(AccountRepositoryInterface::class)
        ->findByIdentifierOrThrowException($accountIdentifier)
        ->getCurrency();
        $this->currencySymbol = $currency->getSymbol();
        return new EntityObjectsCollectionQueryResponse(
            $this->repoLoader->load(StockRepositoryInterface::class)
            ->findByCurrency(
                $currency,
                ($limit ? $limit + 1 : null),
                ($limit ? $page * $limit : 0),
                $sortField,
                $sortDir
            ),
            $limit,
            $page
        );
    }

    public function byCode(string $code): Stock
    {
        return $this->repoLoader->load(StockRepositoryInterface::class)
        ->findByIdOrThrowException($code);
    }
}
