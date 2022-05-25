<?php

namespace xVer\MiCartera\Application\Command\Account;

use DateTimeZone;
use xVer\Bundle\DomainBundle\Application\AbstractApplication;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\CurrencyRepositoryInterface;

class AccountCommand extends AbstractApplication
{
    /**
     * @psalm-param list<string> $roles
     */
    public function create(
        string $email,
        string $password,
        string $currencyIso3,
        DateTimeZone $timezone,
        array $roles,
        bool $agreeTerms
    ): Account {
        if (true !== $agreeTerms) {
            throw new DomainException(
                new TranslationVO(
                    'mustAgreeTerms'
                ),
                'agreeTerms'
            );
        }
        return new Account(
            $this->repoLoader,
            $email,
            $password,
            $this->repoLoader->load(CurrencyRepositoryInterface::class)
            ->findByIdOrThrowException($currencyIso3),
            $timezone,
            $roles
        );
    }
}
