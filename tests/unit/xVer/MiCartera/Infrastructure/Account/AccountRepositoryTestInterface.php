<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\Account;

use xVer\MiCartera\Infrastructure\Account\AccountRepositoryInterface;

interface AccountRepositoryTestInterface
{
    public function testAccountIsAdded(): AccountRepositoryInterface;

    public function testAccountIsFoundByEmail(AccountRepositoryInterface $repo): AccountRepositoryInterface;

    public function testAddingAccountWithExistingEmailThrowsException(AccountRepositoryInterface $repo): void;
}