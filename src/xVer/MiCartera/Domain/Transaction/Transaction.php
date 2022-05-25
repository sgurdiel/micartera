<?php

namespace xVer\MiCartera\Domain\Transaction;

use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;

class Transaction implements EntityInterface
{
    public const TYPE_BUY = 0;
    public const TYPE_SELL = 1;
    public const OUTSTANDING_ADD = 0;
    public const OUTSTANDING_SUB = 1;
    public const NUMBER_BETWEEN_MAX = '99999';
    private Uuid $id;
    private int $amount_outstanding;
    private Currency $currency;
    /**
     * @phpstan-var string
     * @psalm-var numeric-string
     */
    private string $price;
    /**
     * @phpstan-var string
     * @psalm-var numeric-string
     */
    private string $expenses;

    public function __construct(
        private int $type,
        private Stock $stock,
        private \DateTime $datetimeutc,
        private int $amount,
        MoneyVO $expenses,
        private Account $account
    ) {
        if (!in_array($this->type, [self::TYPE_BUY, self::TYPE_SELL])) {
            throw new DomainException(
                new TranslationVO(
                    'invalidTransactionType',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'type'
            );
        }
        if ($this->datetimeutc > new \DateTime('now', new \DateTimeZone('UTC'))) {
            throw new DomainException(
                new TranslationVO(
                    'futureDateNotAllowed',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }
        if (
            0 >= $this->amount
            || 99999 < $this->amount
            ) {
            throw new DomainException(
                new TranslationVO(
                    'numberBetween',
                    ['minimum' => '1', 'maximum' => self::NUMBER_BETWEEN_MAX],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'amount'
            );
        }
        $this->id = Uuid::v4();
        $this->amount_outstanding = ($this->type === self::TYPE_BUY ? $this->amount : 0);
        $this->currency = $this->getStock()->getPrice()->getCurrency();
        $this->setExpenses($expenses);
        $this->setPrice($stock->getPrice());
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof Transaction) {
            throw new \InvalidArgumentException();
        }
        return $this->id->equals($otherEntity->getId());
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getStock(): Stock
    {
        return $this->stock;
    }

    public function getDateTimeUtc(): \DateTime
    {
        return new \DateTime($this->datetimeutc->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'));
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getAmountOutstanding(): int
    {
        return $this->amount_outstanding;
    }

    public function setAmountOutstanding(AccountingMovement $accountingMovement, bool $increase): self
    {
        if (self::TYPE_BUY !== $this->getType()) {
            throw new DomainException(
                new TranslationVO(
                    'transactionAssertType',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                ''
            );
        }

        if (!$this->sameId($accountingMovement->getBuyTransaction())) {
            throw new \InvalidArgumentException();
        }

        if ($increase) {
            $this->amount_outstanding += $accountingMovement->getAmount();
        } else {
            $this->amount_outstanding -= $accountingMovement->getAmount();
        }

        return $this;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getPrice(): StockPriceVO
    {
        return new StockPriceVO($this->price, $this->currency);
    }

    final public function setPrice(StockPriceVO $price): self
    {
        if ($price->getCurrency()->getIso3() !== $this->currency->getIso3()) {
            throw new DomainException(
                new TranslationVO(
                    'invalidCurrency',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'price'
            );
        }
        $this->price = $price->getValue();

        return $this;
    }

    public function getExpenses(): MoneyVO
    {
        return new MoneyVO($this->expenses, $this->currency);
    }

    final public function setExpenses(MoneyVO $expenses): self
    {
        if ($expenses->getCurrency()->getIso3() !== $this->currency->getIso3()) {
            throw new DomainException(
                new TranslationVO(
                    'otherCurrencyExpected',
                    ['received' => $expenses->getCurrency()->getIso3(), 'expected' => $this->currency->getIso3()],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'expenses'
            );
        }

        if (
            -1 === bccomp($expenses->getValue(), "0", StockPriceVO::DECIMALS)
            || 1 === bccomp($expenses->getValue(), "99999.9999", StockPriceVO::DECIMALS)
        ) {
            throw new DomainException(
                new TranslationVO(
                    'numberBetween',
                    ['minimum' => '0', 'maximum' => '99999.9999'],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'price'
            );
        }
        $this->expenses = $expenses->getValue();

        return $this;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }
}
