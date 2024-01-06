<?php

namespace xVer\MiCartera\Domain\Account;

use DateTimeZone;
use Throwable;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Currency\CurrencyRepositoryInterface;
use xVer\Symfony\Bundle\BaseAppBundle\Domain\Account\Account as BaseAccount;

class Account extends BaseAccount
{
    /** @var non-empty-string */
    private readonly string $timezone;

    /**
     * @param list<string> $roles
     */
    public function __construct(
        readonly EntityObjectRepositoryLoaderInterface $repoLoader,
        string $email,
        string $password,
        private readonly Currency $currency,
        DateTimeZone $timezone,
        array $roles = ['ROLE_USER']
    ) {
        parent::__construct($email, $password, $roles);
        $this->timezone = $timezone->getName();
        $this->persistCreate($repoLoader);
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getTimeZone(): DateTimeZone
    {
        return new DateTimeZone($this->timezone);
    }

    protected function persistCreate(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): void {
        if (null === $repoLoader->load(CurrencyRepositoryInterface::class)
        ->findById($this->getCurrency()->getIso3())
        ) {
            throw new DomainException(
                new TranslationVO(
                    'relatedEntityNotPersisted',
                    ['entity' => 'Currency', 'entity2' => 'Account'],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'currency'
            );
        }
        $repoAccount = $repoLoader->load(AccountRepositoryInterface::class);
        if (null !== $repoAccount->findByIdentifier($this->getEmail())
        ) {
            throw new DomainException(
                new TranslationVO(
                    'accountEmailExists',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'email'
            );
        }
        try {
            $repoAccount->persist($this);
            $repoAccount->flush();
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
