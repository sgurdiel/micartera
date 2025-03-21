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
    final public const LENGTH_ISO3 = 3;
    final public const MAX_LENGTH_SYMBOL = 10;
    final public const MIN_LENGTH_SYMBOL = 1;
    final public const MAX_DECIMALS = 4;
    final public const MIN_DECIMALS = 1;

    public function __construct(
        readonly EntityObjectRepositoryLoaderInterface $repoLoader,
        private string $iso3,
        private readonly string $symbol,
        private readonly int $decimals
    ) {
        $this->validIso3();
        $this->validSymbol();
        $this->validDecimals();
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
        return 0 === strcmp($this->getIso3(), $otherEntityObject->getIso3());
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    private function validIso3(): void
    {
        if (strlen($this->iso3) !== self::LENGTH_ISO3) {
            throw new DomainException(
                new TranslationVO(
                    'invalidIso3',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'iso3'
            );
        }
    }

    private function validSymbol(): void
    {
        $symbolLength = strlen($this->symbol);
        if ($symbolLength < self::MIN_LENGTH_SYMBOL || $symbolLength > self::MAX_LENGTH_SYMBOL) {
            throw new DomainException(
                new TranslationVO(
                    'invalidSymbol',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'symbol'
            );
        }
    }

    private function validDecimals(): void
    {
        if ($this->decimals < self::MIN_DECIMALS || $this->decimals > self::MAX_DECIMALS) {
            throw new DomainException(
                new TranslationVO(
                    'enterNumberBetween',
                    ['minimum' => self::MIN_DECIMALS, 'maximum' => self::MAX_DECIMALS],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'amount'
            );
        }
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
