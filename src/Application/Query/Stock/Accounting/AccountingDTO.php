<?php

namespace xVer\MiCartera\Application\Query\Stock\Accounting;

use DateTime;
use xVer\Bundle\DomainBundle\Application\Query\EntityObjectsCollectionQueryResponse;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Stock\Accounting\SummaryVO;
use xVer\MiCartera\Domain\Stock\Accounting\Movement;
use xVer\MiCartera\Domain\Stock\Accounting\MovementsCollection;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;

/**
 * @template-extends EntityObjectsCollectionQueryResponse<Movement>
 */
class AccountingDTO extends EntityObjectsCollectionQueryResponse
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private Movement $movement;
    private ?int $offset = null;

    public function __construct(
        private readonly Account $account,
        MovementsCollection $accountingMovementsCollection,
        private readonly int $displayYear,
        private readonly SummaryVO $summary,
        int $limit = 0,
        readonly int $page = 0
    ) {
        parent::__construct($accountingMovementsCollection, $limit, $page);
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getSummary(): SummaryVO
    {
        return $this->summary;
    }

    public function getCurrentYear(): int
    {
        return (int) (new DateTime('now', $this->account->getTimeZone()))->format('Y');
    }

    public function getDisplayedYear(): int
    {
        return $this->displayYear;
    }

    public function getMovementAcquisitionPrice(int $offset): StockPriceVO
    {
        $this->setCollectionKey($offset);
        return $this->movement->getAcquisitionPrice();
    }

    public function getMovementLiquidationPrice(int $offset): StockPriceVO
    {
        $this->setCollectionKey($offset);
        return $this->movement->getLiquidationPrice();
    }

    public function getMovementAcquisitionExpense(int $offset): MoneyVO
    {
        $this->setCollectionKey($offset);
        return $this->movement->getAcquisitionExpenses();
    }

    public function getMovementLiquidationExpense(int $offset): MoneyVO
    {
        $this->setCollectionKey($offset);
        return $this->movement->getLiquidationExpenses();
    }

    public function getMovementProfitPrice(int $offset): MoneyVO
    {
        return $this->getMovementLiquidationPrice($offset)->toMoney()->subtract(
            $this->getMovementAcquisitionPrice($offset)->toMoney()->add(
                $this->getMovementAcquisitionExpense($offset)->add(
                    $this->getMovementLiquidationExpense($offset)
                )
            )
        );
    }

    public function getMovementProfitPercentage(int $offset): string
    {
        return $this->getMovementAcquisitionPrice($offset)->toMoney()->percentageDifference(
            $this->getMovementLiquidationPrice($offset)->toMoney()->subtract(
                $this->getMovementAcquisitionExpense($offset)->add(
                    $this->getMovementLiquidationExpense($offset)
                )
            )
        );
    }

    private function setCollectionKey(int $offset): void
    {
        if ($this->offset !== $offset) {
            $movement = $this->getCollection()->offsetGet($offset);
            if (is_null($movement)) {
                throw new DomainException(
                    new TranslationVO('collectionInvalidOffsetPosition')
                );
            }
            $this->movement = $movement;
            $this->offset = $offset;
        }
    }
}
