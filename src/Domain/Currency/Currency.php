<?php

namespace xVer\MiCartera\Domain\Currency;

use InvalidArgumentException;
use Throwable;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;

class Currency implements EntityObjectInterface
{
    public function __construct(
        readonly EntityObjectRepositoryLoaderInterface $repoLoader,
        private string $iso3,
        private readonly string $symbol,
        private readonly int $decimals
    ) {
        if (3 !== strlen($this->iso3)) {
            throw new DomainException(
                new TranslationVO(
                    'invalidIso3',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'iso3'
            );
        }
        if (0 >= strlen($this->symbol) || strlen($this->symbol) > 10) {
            throw new DomainException(
                new TranslationVO(
                    'invalidSymbol',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'symbol'
            );
        }
        if (0 >= $this->decimals || 4 < $this->decimals) {
            throw new DomainException(
                new TranslationVO(
                    'numberBetween',
                    ['minimum' => '1', 'maximum' => '4'],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'amount'
            );
        }
        $this->iso3 = strtoupper($this->iso3);
        $this->persistCreate($repoLoader);
    }

    public function getIso3(): string
    {
        return $this->iso3;
    }

    public function sameId(EntityObjectInterface $otherEntityObject): bool
    {
        if (!$otherEntityObject instanceof Currency) {
            throw new InvalidArgumentException();
        }
        return (0 === strcmp($this->getIso3(), $otherEntityObject->getIso3()));
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    private function persistCreate(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): void {
        $repoCurrency = $repoLoader->load(CurrencyRepositoryInterface::class);
        if (null !== $repoCurrency->findById($this->getIso3())) {
            throw new DomainException(
                new TranslationVO(
                    'currencyExists',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'email'
            );
        }
        try {
            $repoCurrency->persist($this);
            $repoCurrency->flush();
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
