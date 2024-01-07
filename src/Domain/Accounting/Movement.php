<?php

namespace xVer\MiCartera\Domain\Accounting;

use InvalidArgumentException;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\NumberOperation;
use xVer\MiCartera\Domain\Stock\Transaction\Adquisition;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;

class Movement implements EntityObjectInterface
{
    private int $amount;
    /** @var numeric-string */
    private string $adquisitionPrice;
    /** @var numeric-string */
    private string $liquidationPrice;
    /** @var numeric-string */
    private string $adquisitionExpenses;
    /** @var numeric-string */
    private string $liquidationExpenses;

    public function __construct(
        readonly EntityObjectRepositoryLoaderInterface $repoLoader,
        private readonly Adquisition $adquisition,
        private readonly Liquidation $liquidation
    ) {
        if (false === $this->adquisition->getStock()->sameId($this->liquidation->getStock())) {
            throw new DomainException(
                new TranslationVO(
                    'transactionAssertStock',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
        if ($this->adquisition->getDateTimeUtc() > $this->liquidation->getDateTimeUtc()) {
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
        $this->setAdquisitionPrice();
        $this->setLiquidationPrice();
        $this->setAdquisitionExpenses();
        $this->setLiquidationExpenses();
        $this->adquisition->accountMovement($repoLoader, $this);
        $this->liquidation->accountMovement($repoLoader, $this);
        $repoLoader->load(MovementRepositoryInterface::class)->persist($this);
    }

    public function sameId(EntityObjectInterface $otherEntityObject): bool
    {
        if (!$otherEntityObject instanceof Movement) {
            throw new InvalidArgumentException();
        }
        return
            $this->adquisition->sameId($otherEntityObject->getAdquisition())
            && $this->liquidation->sameId($otherEntityObject->getLiquidation());
    }

    public function getAdquisition(): Adquisition
    {
        return $this->adquisition;
    }

    public function getLiquidation(): Liquidation
    {
        return $this->liquidation;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    private function setAmount(): void
    {
        if (
            0 >= $this->adquisition->getAmountOutstanding()
            ||
            0 >= $this->liquidation->getAmountRemaining()
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
        $this->amount = (
            $this->liquidation->getAmountRemaining() >= $this->adquisition->getAmountOutstanding()
            ? $this->adquisition->getAmountOutstanding()
            : $this->liquidation->getAmountRemaining()
        );
    }

    private function setAdquisitionPrice(): void
    {
        $this->adquisitionPrice = $this->adquisition->getPrice()
        ->multiply(
            (string) $this->getAmount()
        )
        ->toMoney()
        ->getValue();
    }

    private function setLiquidationPrice(): void
    {
        $this->liquidationPrice = $this->liquidation->getPrice()
        ->multiply(
            (string) $this->getAmount()
        )
        ->toMoney()
        ->getValue();
    }

    private function setAdquisitionExpenses(): void
    {
        $this->adquisitionExpenses = (
            $this->getAmount() === $this->adquisition->getAmountOutstanding()
            ?
            $this->adquisition->getExpensesUnaccountedFor()->getValue()
            :
            $this->adquisition->getExpensesUnaccountedFor()->multiply(
                NumberOperation::divide(
                    $this->adquisition->getExpensesUnaccountedFor()->getCurrency()->getDecimals(),
                    (string) $this->getAmount(),
                    (string) $this->adquisition->getAmountOutstanding()
                )
            )->getValue()
        );
    }

    private function setLiquidationExpenses(): void
    {
        $this->liquidationExpenses = (
            $this->getAmount() === $this->liquidation->getAmountRemaining()
            ?
            $this->liquidation->getExpensesUnaccountedFor()->getValue()
            :
            $this->liquidation->getExpensesUnaccountedFor()->multiply(
                NumberOperation::divide(
                    $this->liquidation->getExpensesUnaccountedFor()->getCurrency()->getDecimals(),
                    (string) $this->getAmount(),
                    (string) $this->liquidation->getAmountRemaining()
                )
            )->getValue()
        );
    }

    public function getAdquisitionPrice(): MoneyVO
    {
        return new MoneyVO($this->adquisitionPrice, $this->getAdquisition()->getCurrency());
    }

    public function getLiquidationPrice(): MoneyVO
    {
        return new MoneyVO($this->liquidationPrice, $this->getAdquisition()->getCurrency());
    }

    public function getAdquisitionExpenses(): MoneyVO
    {
        return new MoneyVO($this->adquisitionExpenses, $this->getAdquisition()->getCurrency());
    }

    public function getLiquidationExpenses(): MoneyVO
    {
        return new MoneyVO($this->liquidationExpenses, $this->getAdquisition()->getCurrency());
    }
}
