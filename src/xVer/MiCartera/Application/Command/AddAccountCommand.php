<?php

namespace xVer\MiCartera\Application\Command;

use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryInterface;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInterface;

class AddAccountCommand
{
    public function execute(AccountRepositoryInterface $repo, CurrencyRepositoryInterface $repoCurrency, Account $account): Account
    {
        $account = $repo->add($account, $repoCurrency);

        return $account;
    }
}
