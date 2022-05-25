<?php

namespace xVer\MiCartera\Application\Query\Accounting;

use DateTime;
use xVer\Bundle\DomainBundle\Application\Query\EntityObjectsCollectionQueryResponse;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Accounting\SummaryVO;
use xVer\MiCartera\Domain\Accounting\Movement;
use xVer\MiCartera\Domain\Accounting\MovementsCollection;
use xVer\MiCartera\Domain\MoneyVO;

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
        private readonly int $page = 0
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

    public function getMovementAdquisitionPrice(int $offset): MoneyVO
    {
        $this->setCollectionKey($offset);
        return $this->movement->getAdquisitionPrice();
    }

    public function getMovementLiquidationPrice(int $offset): MoneyVO
    {
        $this->setCollectionKey($offset);
        return $this->movement->getLiquidationPrice();
    }

    public function getMovementAdquisitionExpense(int $offset): MoneyVO
    {
        $this->setCollectionKey($offset);
        return $this->movement->getAdquisitionExpenses();
    }

    public function getMovementLiquidationExpense(int $offset): MoneyVO
    {
        $this->setCollectionKey($offset);
        return $this->movement->getLiquidationExpenses();
    }

    public function getMovementProfitPrice(int $offset): MoneyVO
    {
        return $this->getMovementLiquidationPrice($offset)->subtract(
            $this->getMovementAdquisitionPrice($offset)->add(
                $this->getMovementAdquisitionExpense($offset)->add(
                    $this->getMovementLiquidationExpense($offset)
                )
            )
        );
    }

    public function getMovementProfitPercentage(int $offset): string
    {
        return $this->getMovementAdquisitionPrice($offset)->percentageDifference(
            $this->getMovementLiquidationPrice($offset)->subtract(
                $this->getMovementAdquisitionExpense($offset)->add(
                    $this->getMovementLiquidationExpense($offset)
                )
            )
        );
    }

    private function setCollectionKey(int $offset): void
    {
        if ($this->offset !== $offset) {
            if (is_null($movement = $this->getCollection()->offsetGet($offset))) {
                throw new DomainException(
                    new TranslationVO('collectionInvalidOffsetPosition')
                );
            }
            $this->movement = $movement;
            $this->offset = $offset;
        }
    }
}
