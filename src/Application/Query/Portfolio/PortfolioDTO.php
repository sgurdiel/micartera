<?php

namespace xVer\MiCartera\Application\Query\Portfolio;

use InvalidArgumentException;
use xVer\Bundle\DomainBundle\Application\Query\EntityObjectsCollectionQueryResponse;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Portfolio\SummaryVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Stock\Transaction\Adquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection;

/**
 * @template-extends EntityObjectsCollectionQueryResponse<Adquisition>
 */
class PortfolioDTO extends EntityObjectsCollectionQueryResponse
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private Adquisition $position;
    private ?int $offset = null;

    public function __construct(
        private readonly Account $account,
        AdquisitionsCollection $outstandingPositionsCollection,
        private readonly SummaryVO $summary,
        int $limit = 0,
        private readonly int $page = 0
    ) {
        parent::__construct($outstandingPositionsCollection, $limit, $page);
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getSummary(): SummaryVO
    {
        return $this->summary;
    }

    public function getPositionAdquisitionPrice(int $offset): StockPriceVO
    {
        $this->setCollectionKey($offset);
        return $this->position->getPrice()->multiply(
            (string) $this->position->getAmountOutstanding()
        );
    }

    public function getPositionMarketPrice(int $offset): StockPriceVO
    {
        $this->setCollectionKey($offset);
        return $this->position->getStock()->getPrice()->multiply(
            (string) $this->position->getAmountOutstanding()
        );
    }

    public function getPositionAdquisitionExpenses(int $offset): MoneyVO
    {
        $this->setCollectionKey($offset);
        return $this->position->getExpensesUnaccountedFor();
    }

    public function getPositionProfitPrice(int $offset): MoneyVO
    {
        return $this->getPositionMarketPrice($offset)->toMoney()->subtract(
            $this->getPositionAdquisitionPrice($offset)->toMoney()->add(
                $this->getPositionAdquisitionExpenses($offset)
            )
        );
    }

    public function getPositionProfitPercentage(int $offset): string
    {
        return $this->getPositionAdquisitionPrice($offset)->toMoney()->percentageDifference(
            $this->getPositionMarketPrice($offset)->toMoney()->subtract(
                $this->getPositionAdquisitionExpenses($offset)
            )
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private function setCollectionKey(int $offset): void
    {
        if ($this->offset !== $offset) {
            if (is_null($position = $this->getCollection()->offsetGet($offset))) {
                throw new DomainException(
                    new TranslationVO('collectionInvalidOffsetPosition')
                );
            }
            $this->position = $position;
            $this->offset = $offset;
        }
    }
}
