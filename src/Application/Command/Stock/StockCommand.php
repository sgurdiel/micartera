<?php

namespace xVer\MiCartera\Application\Command\Stock;

use xVer\Bundle\DomainBundle\Application\AbstractApplication;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Exchange\Exchange;
use xVer\MiCartera\Domain\Exchange\ExchangeRepositoryInterface;
use xVer\MiCartera\Domain\Stock\StockRepositoryInterface;

class StockCommand extends AbstractApplication
{
    /**
     * @psalm-param numeric-string $price
     */
    public function create(string $code, string $name, string $price, string $accountIdentifier, string $exchange): Stock
    {
        return new Stock(
            $this->repoLoader,
            $code,
            $name,
            new StockPriceVO(
                $price,
                $this->repoLoader->load(AccountRepositoryInterface::class)
                ->findByIdentifierOrThrowException($accountIdentifier)
                ->getCurrency()
            ),
            $this->repoLoader->load(ExchangeRepositoryInterface::class)
            ->findByIdOrThrowException($exchange)
        );
    }

    /**
     * @psalm-param numeric-string $price
     */
    public function update(string $code, string $name, string $price): Stock
    {
        $stock = $this->repoLoader->load(StockRepositoryInterface::class)
        ->findByIdOrThrowException($code);
        $stock->setName($name);
        $stock->setPrice(
            new StockPriceVO(
                $price,
                $stock->getCurrency()
            )
        );
        return $stock->persistUpdate($this->repoLoader);
    }

    public function delete(string $code): void
    {
        $this->repoLoader->load(StockRepositoryInterface::class)
        ->findByIdOrThrowException($code)
        ->persistRemove($this->repoLoader);
    }
}
