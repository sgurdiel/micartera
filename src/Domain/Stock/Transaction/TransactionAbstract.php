<?php

namespace xVer\MiCartera\Domain\Stock\Transaction;

use DateTime;
use DateTimeZone;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Accounting\Movement;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Stock\Transaction\Criteria\FiFoCriteria;

abstract class TransactionAbstract
{
    protected Uuid $id;
    protected readonly Currency $currency;
    /** @var numeric-string */
    protected readonly string $price;
    /** @var numeric-string */
    protected string $expenses;
    /** @var numeric-string */
    protected string $expensesUnaccountedFor = '0';
    /** @var numeric-string */
    protected readonly string $amount;

    public function __construct(
        protected readonly Stock $stock,
        protected readonly DateTime $datetimeutc,
        TransactionAmountVO $amount,
        MoneyVO $expenses,
        protected readonly Account $account
    ) {
        $this->amount = $amount->getValue();
        $this->constraintNoTransactionDateInFuture();
        $this->generateId();
        $this->currency = $this->getStock()->getPrice()->getCurrency();
        $this->price = $this->stock->getPrice()->getValue();
        $this->setExpenses($expenses);
    }

    private function constraintNoTransactionDateInFuture(): void
    {
        if ($this->datetimeutc > new DateTime('now', new DateTimeZone('UTC'))) {
            throw new DomainException(
                new TranslationVO(
                    'futureDateNotAllowed',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
    }

    private function generateId(): void
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    abstract public function sameId(EntityObjectInterface $otherEntityObject): bool;

    public function getStock(): Stock
    {
        return $this->stock;
    }

    public function getDateTimeUtc(): DateTime
    {
        return new DateTime($this->datetimeutc->format('Y-m-d H:i:s'), new DateTimeZone('UTC'));
    }

    public function getAmount(): TransactionAmountVO
    {
        return new TransactionAmountVO($this->amount);
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getPrice(): StockPriceVO
    {
        return new StockPriceVO(
            $this->price,
            $this->getCurrency()
        );
    }

    public function getExpenses(): MoneyVO
    {
        return new MoneyVO(
            $this->expenses,
            $this->getCurrency()
        );
    }

    private function setExpenses(MoneyVO $expenses): self
    {
        if (false === $expenses->getCurrency()->sameId($this->getCurrency())) {
            throw new DomainException(
                new TranslationVO(
                    'otherCurrencyExpected',
                    ['received' => $expenses->getCurrency()->getIso3(), 'expected' => $this->getCurrency()->getIso3()],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'expenses'
            );
        }

        $highestExpenseAllowed = (new MoneyVO('0', $expenses->getCurrency()))->getHighestValue();

        if (
            $expenses->smaller(new MoneyVO('0', $expenses->getCurrency()))
            ||
            $expenses->greater(new MoneyVO($highestExpenseAllowed, $expenses->getCurrency()))
        ) {
            throw new DomainException(
                new TranslationVO(
                    'enterNumberBetween',
                    ['minimum' => '0', 'maximum' => $highestExpenseAllowed],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'price'
            );
        }
        $this->expenses = $expenses->getValue();
        $this->expensesUnaccountedFor = $this->expenses;

        return $this;
    }

    protected function setExpensesUnaccountedFor(MoneyVO $delta, bool $subtract): void
    {
        $this->expensesUnaccountedFor = (
            $subtract
            ?
            $this->getExpensesUnaccountedFor()->subtract(
                $delta
            )->getValue()
            :
            $this->getExpensesUnaccountedFor()->add(
                $delta
            )->getValue()
        );
        if (
            $this->getExpensesUnaccountedFor()->smaller(new MoneyVO('0', $this->getExpensesUnaccountedFor()->getCurrency()))
            ||
            $this->getExpensesUnaccountedFor()->greater($this->getExpenses())
        ) {
            throw new DomainException(
                new TranslationVO(
                    'InvalidMovementExpensesAmount',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
    }

    public function getExpensesUnaccountedFor(): MoneyVO
    {
        return new MoneyVO(
            $this->expensesUnaccountedFor,
            $this->getCurrency()
        );
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    abstract public function accountMovement(
        EntityObjectRepositoryLoaderInterface $repoLoader,
        Movement $movement
    ): self;

    abstract protected function persistCreate(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): void;

    abstract public function persistRemove(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): void;

    protected function fiFoCriteriaInstance(EntityObjectRepositoryLoaderInterface $repoLoader): FiFoCriteria
    {
        return new FiFoCriteria($repoLoader);
    }
}
