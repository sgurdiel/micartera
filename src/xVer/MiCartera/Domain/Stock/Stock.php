<?php

namespace xVer\MiCartera\Domain\Stock;

use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Currency\Currency;

class Stock implements EntityInterface
{
    private string $code;
    private string $name;
    private Currency $currency;
    /**
     * @phpstan-var string
     * @psalm-var numeric-string
     */
    private string $price;

    public function __construct(string $code, string $name, StockPriceVO $price)
    {
        $this->setCode($code);
        $this->setName($name);
        $this->currency = $price->getCurrency();
        $this->setPrice($price);
    }

    public function getId(): string
    {
        return $this->code;
    }

    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof Stock) {
            throw new \InvalidArgumentException();
        }
        return 0 === strcmp($this->code, $otherEntity->getId());
    }

    private function setCode(string $code): self
    {
        $length = mb_strlen($code);
        if ($length > 4 || $length === 0) {
            throw new DomainException(
                new TranslationVO(
                    'stringLength',
                    ['minimum' => 1, 'maximum' => 4],
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
        if ($length > 255 || $length === 0) {
            throw new DomainException(
                new TranslationVO(
                    'stringLength',
                    ['minimum' => 1, 'maximum' => 255],
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
        if (false === $this->currency->sameId($price->getCurrency())) {
            throw new DomainException(
                new TranslationVO(
                    'otherCurrencyExpected',
                    ['received' => $price->getCurrency()->getIso3(), 'expected' => $this->currency->getIso3()],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'price'
            );
        }
        $this->price = $price->getValue();

        return $this;
    }
}
