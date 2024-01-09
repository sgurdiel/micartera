<?php

namespace xVer\MiCartera\Domain\Stock;

use InvalidArgumentException;
use Throwable;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionRepositoryInterface;

class Stock implements EntityObjectInterface
{
    final public const MAX_TRANSACTION_AMOUNT = 999999;
    final public const MAX_CODE_LENGTH = 4;
    final public const MIN_CODE_LENGTH = 1;
    final public const MAX_NAME_LENGTH = 255;
    final public const MIN_NAME_LENGTH = 1;
    private string $code;
    private string $name;
    private readonly Currency $currency;
    /** @psalm-var numeric-string */
    private string $price;

    public function __construct(
        readonly EntityObjectRepositoryLoaderInterface $repoLoader,
        string $code,
        string $name,
        StockPriceVO $price
    ) {
        $this->setCode($code);
        $this->setName($name);
        $this->currency = $price->getCurrency();
        $this->price = $price->getValue();
        $this->persistCreate($repoLoader);
    }

    public function getId(): string
    {
        return $this->code;
    }

    public function sameId(EntityObjectInterface $otherEntityObject): bool
    {
        if (!$otherEntityObject instanceof Stock) {
            throw new InvalidArgumentException();
        }
        return 0 === strcmp($this->getId(), $otherEntityObject->getId());
    }

    private function setCode(string $code): self
    {
        $length = mb_strlen($code);
        if ($length > self::MAX_CODE_LENGTH || $length < self::MIN_CODE_LENGTH) {
            throw new DomainException(
                new TranslationVO(
                    'stringLength',
                    ['minimum' => self::MIN_CODE_LENGTH, 'maximum' => self::MAX_CODE_LENGTH],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'code'
            );
        }
        $this->code = mb_strtoupper($code);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    final public function setName(string $name): self
    {
        $length = mb_strlen($name);
        if ($length > self::MAX_NAME_LENGTH || $length <  self::MIN_NAME_LENGTH) {
            throw new DomainException(
                new TranslationVO(
                    'stringLength',
                    ['minimum' => self::MIN_NAME_LENGTH, 'maximum' => self::MAX_NAME_LENGTH],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'name'
            );
        }
        $this->name = $name;

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
        if (false === $this->getCurrency()->sameId($price->getCurrency())) {
            throw new DomainException(
                new TranslationVO(
                    'otherCurrencyExpected',
                    [
                        'received' => $price->getCurrency()->getIso3(),
                        'expected' => $this->getCurrency()->getIso3()
                    ],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'price'
            );
        }
        $this->price = $price->getValue();

        return $this;
    }

    private function persistCreate(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): void {
        $repoStock = $repoLoader->load(StockRepositoryInterface::class);
        if (null !== $repoStock->findById($this->getId())
        ) {
            throw new DomainException(
                new TranslationVO(
                    'stockExists',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'code'
            );
        }
        try {
            $repoStock->persist($this);
            $repoStock->flush();
        } catch (Throwable) {
            throw new DomainException(
                new TranslationVO(
                    'actionFailed',
                    [],
                    TranslationVO::DOMAIN_MESSAGES
                )
            );
        }
    }

    public function persistUpdate(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): self {
        $repoStock = $repoLoader->load(StockRepositoryInterface::class);
        try {
            $repoStock->persist($this);
            $repoStock->flush();
        } catch (Throwable) {
            throw new DomainException(
                new TranslationVO(
                    'actionFailed',
                    [],
                    TranslationVO::DOMAIN_MESSAGES
                )
            );
        }
        return $this;
    }

    public function persistRemove(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): void {
        if (
            $repoLoader->load(AdquisitionRepositoryInterface::class)
            ->findByStockId($this)->count() !== 0
        ) {
            throw new DomainException(
                new TranslationVO(
                    'stockHasTransactions',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'code'
            );
        }
        $repoStock = $repoLoader->load(StockRepositoryInterface::class);
        try {
            $repoStock->remove($this);
            $repoStock->flush();
        } catch (Throwable) {
            throw new DomainException(
                new TranslationVO(
                    'actionFailed',
                    [],
                    TranslationVO::DOMAIN_MESSAGES
                )
            );
        }
    }
}
