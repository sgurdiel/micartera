<?php

namespace xVer\MiCartera\Infrastructure\Account;

use xVer\Bundle\DomainBundle\Infrastructure\Account\AccountRepositoryInterface as BaseAccountRepositoryInterface;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInterface;

interface AccountRepositoryInterface extends BaseAccountRepositoryInterface
{
    public function add(Account $account, CurrencyRepositoryInterface $repoCurrency): Account;

    public function createConstraints(Account $account, CurrencyRepositoryInterface $repoCurrency): void;
}
