<?php

namespace xVer\MiCartera\Domain\Account;

use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\Account\AccountInterface;
use xVer\Bundle\DomainBundle\Domain\EntityInterface;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;

class Account implements AccountInterface
{
    private Uuid $id;
    private string $email;
    /** @var array<string> $roles */
    private array $roles = [];
    private string $timezone;
    public const AVAILABLE_ROLES = ['ROLE_ADMIN','ROLE_USER'];

    /**
     * @param array<string> $roles
     */
    public function __construct(string $email, private string $password, private Currency $currency, \DateTimeZone $timezone, array $roles = ['ROLE_USER'])
    {
        $this->id = Uuid::v4();
        $this->setEmail($email);
        $this->setRoles($roles);
        $this->timezone = $timezone->getName();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof Account) {
            throw new \InvalidArgumentException();
        }
        return $this->id->equals($otherEntity->getId());
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    final public function setEmail(string $email): self
    {
        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException(
                new TranslationVO('error_invalid_email', [], TranslationVO::DOMAIN_VALIDATORS), 'email'
            );
        }
        $this->email = $email;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array<string> $roles
     */
    private function setRoles(array $roles): self
    {
        foreach ($roles as $role) {
            if (!in_array($role, self::AVAILABLE_ROLES)) {
                throw new DomainException(
                    new TranslationVO(
                        'invalidUserRole',
                        [],
                        TranslationVO::DOMAIN_VALIDATORS
                    ),
                    'role'
                );
            }
        }
        $this->roles = $roles;
        // guarantee every user at least has ROLE_USER
        if (!in_array('ROLE_USER', $this->roles)) {
            array_push($this->roles, 'ROLE_USER');
        }

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    final public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getTimeZone(): \DateTimeZone
    {
        return new \DateTimeZone($this->timezone);
    }
}
