<?php

namespace xVer\MiCartera\Domain\Stock\Accounting;

use InvalidArgumentException;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Stock\Transaction\Acquisition;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountOutstandingVO;

class Movement implements EntityObjectInterface
{
    /** @var numeric-string */
    private string $amount;
    /** @var numeric-string */
    private string $acquisitionPrice;
    /** @var numeric-string */
    private string $liquidationPrice;
    /** @var numeric-string */
    private string $acquisitionExpenses;
    /** @var numeric-string */
    private string $liquidationExpenses;

    public function __construct(
        readonly EntityObjectRepositoryLoaderInterface $repoLoader,
        private readonly Acquisition $acquisition,
        private readonly Liquidation $liquidation
    ) {
        if (false === $this->acquisition->getStock()->sameId($this->liquidation->getStock())) {
            throw new DomainException(
                new TranslationVO(
                    'transactionAssertStock',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
        if ($this->acquisition->getDateTimeUtc() > $this->liquidation->getDateTimeUtc()) {
            throw new DomainException(
                new TranslationVO(
                    'accountingMovementAssertDateTime',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
        $this->setAmount();
        $this->setAcquisitionPrice();
        $this->setLiquidationPrice();
        $this->setAcquisitionExpenses();
        $this->setLiquidationExpenses();
        $this->acquisition->accountMovement($repoLoader, $this);
        $this->liquidation->accountMovement($repoLoader, $this);
        $repoLoader->load(MovementRepositoryInterface::class)->persist($this);
    }

    public function sameId(EntityObjectInterface $otherEntityObject): bool
    {
        if (!$otherEntityObject instanceof Movement) {
            throw new InvalidArgumentException();
        }
        return
            $this->acquisition->sameId($otherEntityObject->getAcquisition())
            && $this->liquidation->sameId($otherEntityObject->getLiquidation());
    }

    public function getAcquisition(): Acquisition
    {
        return $this->acquisition;
    }

    public function getLiquidation(): Liquidation
    {
        return $this->liquidation;
    }

    public function getAmount(): TransactionAmountVO
    {
        return new TransactionAmountVO($this->amount);
    }

    private function setAmount(): void
    {
        if (
            $this->acquisition->getAmountOutstanding()->same(new TransactionAmountOutstandingVO('0'))
            ||
            $this->liquidation->getAmountRemaining()->same(new TransactionAmountOutstandingVO('0'))
        ) {
            throw new DomainException(
                new TranslationVO(
                    'accountingMovementAmount',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
        $this->amount =
            $this->acquisition->getAmountOutstanding()->smaller($this->liquidation->getAmountRemaining())
            ?
            $this->acquisition->getAmountOutstanding()->getValue()
            :
            $this->liquidation->getAmountRemaining()->getValue()
        ;
    }

    private function setAcquisitionPrice(): void
    {
        $this->acquisitionPrice = $this->acquisition->getPrice()
        ->multiply(
            $this->getAmount()
        )
        ->getValue();
    }

    private function setLiquidationPrice(): void
    {
        $this->liquidationPrice = $this->liquidation->getPrice()
        ->multiply(
            $this->getAmount()
        )
        ->getValue();
    }

    private function setAcquisitionExpenses(): void
    {
        $this->acquisitionExpenses = (
            $this->getAmount()->same($this->acquisition->getAmountOutstanding())
            ?
            $this->acquisition->getExpensesUnaccountedFor()->getValue()
            :
            $this->acquisition->getExpensesUnaccountedFor()->multiply(
                $this->getAmount()->divide(
                    $this->acquisition->getAmountOutstanding()
                )
            )->getValue()
        );
    }

    private function setLiquidationExpenses(): void
    {
        $this->liquidationExpenses = (
            $this->getAmount()->same($this->liquidation->getAmountRemaining())
            ?
            $this->liquidation->getExpensesUnaccountedFor()->getValue()
            :
            $this->liquidation->getExpensesUnaccountedFor()->multiply(
                $this->getAmount()->divide(
                    $this->liquidation->getAmountRemaining()
                )
            )->getValue()
        );
    }

    public function getAcquisitionPrice(): StockPriceVO
    {
        return new StockPriceVO($this->acquisitionPrice, $this->getAcquisition()->getCurrency());
    }

    public function getLiquidationPrice(): StockPriceVO
    {
        return new StockPriceVO($this->liquidationPrice, $this->getAcquisition()->getCurrency());
    }

    public function getAcquisitionExpenses(): MoneyVO
    {
        return new MoneyVO($this->acquisitionExpenses, $this->getAcquisition()->getCurrency());
    }

    public function getLiquidationExpenses(): MoneyVO
    {
        return new MoneyVO($this->liquidationExpenses, $this->getAcquisition()->getCurrency());
    }
}
