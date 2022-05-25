<?php

namespace Tests\unit\xVer\MiCartera\Infrastructure\AccountingMovement;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInterface;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

abstract class AccountingMovementRepositoryTestAbstract extends KernelTestCase implements AccountingMovementRepositoryTestInterface
{
    protected AccountingMovementRepositoryInterface $repo;
    protected TransactionRepositoryInterface $repoTrans;
    protected Account $account;
    protected MoneyVO $expenses;
    protected Stock $stock;
    protected Transaction $buyTransaction;
    /** @var Transaction[] */
    protected array $tearDownTrans = [];

    // Since AccountingMovement object creation and deletion directly tied to 
    // Transaction object creation and deletion, testing is handled there

    public function testAddDoesNotStoreInDB(): void
    {   
        $sellTransNotPersisted = new Transaction(Transaction::TYPE_SELL, $this->buyTransaction->getStock(), new \DateTime('2021-09-21 12:13:04', new \DateTimeZone('UTC')), 100, $this->buyTransaction->getExpenses(), $this->buyTransaction->getAccount());
        $accountingMovement = new AccountingMovement($this->buyTransaction, $sellTransNotPersisted, 200);
        $accountingMovement = $this->repo->add($accountingMovement, $this->repoTrans);
        $accountingMovement = $this->repo->findByBuyAndSellTransactionIds($this->buyTransaction->getId(), $sellTransNotPersisted->getId());
        $this->assertNull($accountingMovement);
    }

    public function testIsFoundBySellId(): void
    {
        $sellTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('now', new \DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $sellTransaction = $this->repoTrans->add($sellTransaction, $this->repo);
        $this->tearDownTrans[] = $sellTransaction;
        $accountingMovements = $this->repo->findBySellTransactionId($sellTransaction->getId());
        $this->assertIsArray($accountingMovements);
        $this->assertCount(1, $accountingMovements);
        $this->assertTrue($sellTransaction->sameId($accountingMovements[0]->getSellTransaction()));
    }

    public function testfindByIdOrThrowExceptionWithNonExistingThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('expectedPersistedObjectNotFound');
        $sellTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('now', new \DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $accountingMovement = new AccountingMovement($this->buyTransaction, $sellTransaction, 100);
        $this->repo->findByIdOrThrowException($accountingMovement);
    }

    public function testNotFoundByAccountReturnsEmptyArray(): void
    {
        $auxAccount = new Account("test666@example.com", "password", $this->buyTransaction->getCurrency(), $this->buyTransaction->getAccount()->getTimeZone(), ['ROLE_USER']);
        $accountingMovements = $this->repo->findByAccountAndYear($auxAccount, 2022, 0, 10);
        $this->assertIsArray($accountingMovements);
        $this->assertCount(0, $accountingMovements);
    }

    public function testNotFoundBySellIdReturnsEmptyArray(): void
    {
        $sellTransNotPersisted = new Transaction(Transaction::TYPE_SELL, $this->buyTransaction->getStock(), new \DateTime('2021-09-21 12:13:06', new \DateTimeZone('UTC')), 100, $this->buyTransaction->getExpenses(), $this->buyTransaction->getAccount());
        $accountingMovements = $this->repo->findBySellTransactionId($sellTransNotPersisted->getId());
        $this->assertIsArray($accountingMovements);
        $this->assertCount(0, $accountingMovements);
    }

    public function testIsFoundByBuyAndSellIds(): void
    {
        $sellTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('now', new \DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $sellTransaction = $this->repoTrans->add($sellTransaction, $this->repo);
        $this->tearDownTrans[] = $sellTransaction;
        $accountingMovement = $this->repo->findByBuyAndSellTransactionIds(
            $this->buyTransaction->getId(),
            $sellTransaction->getId()
        );
        $this->assertInstanceOf(AccountingMovement::class, $accountingMovement);
        $this->assertTrue($this->buyTransaction->sameId($accountingMovement->getBuyTransaction()));
        $this->assertTrue($sellTransaction->sameId($accountingMovement->getSellTransaction()));
    }

    public function testNotFoundByBuyAndSellIdsReturnsNull(): void
    {
        $sellTransNotPersisted = new Transaction(Transaction::TYPE_SELL, $this->buyTransaction->getStock(), new \DateTime('2021-09-21 12:13:02', new \DateTimeZone('UTC')), 100, $this->buyTransaction->getExpenses(), $this->buyTransaction->getAccount());
        $accountingMovements = $this->repo->findByBuyAndSellTransactionIds(
            $this->buyTransaction->getId(),
            $sellTransNotPersisted->getId()
        );
        $this->assertNull($accountingMovements);
    }

    //public function testAlreadyExistsThrowsException(): void
    //{
    //    $this->expectException(DomainException::class);
    //    $this->expectExceptionMessage(serialize('accountingMovementAssertNotExists');
    //    $accountingMovement = new AccountingMovement(self::$buyTransaction, self::$sellTransaction, 200);
    //    $accountingMovement = $this->repo->add($accountingMovement, $this->repoTrans);
    //    self::$managerRegistry->getManager()->flush();
    //    self::$managerRegistry->getManager()->clear();
    //}

    public function testIsFoundByAccountAndYear(): void
    {
        $dateTime = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        $sellTransaction1 = new Transaction(Transaction::TYPE_SELL, $this->stock, $dateTime, 10, $this->expenses, $this->account);
        $sellTransaction1 = $this->repoTrans->add($sellTransaction1, $this->repo);
        $this->tearDownTrans[] = $sellTransaction1;
        $dateTime2 = clone $dateTime;
        $sellTransaction2 = new Transaction(Transaction::TYPE_SELL, $this->stock, $dateTime2->add(new \DateInterval('PT10S')), 10, $this->expenses, $this->account);
        $sellTransaction2 = $this->repoTrans->add($sellTransaction2, $this->repo);
        $this->tearDownTrans[] = $sellTransaction2;
        $accountingMovements = $this->repo->findByAccountAndYear($sellTransaction1->getAccount(), $dateTime->format('Y'), 0, 1);
        $this->assertIsArray($accountingMovements);
        $this->assertCount(1, $accountingMovements);
        $this->assertTrue($this->buyTransaction->sameId($accountingMovements[0]->getBuyTransaction()));
    }

    public function testNotFoundByAccountAndYearReturnsEmptyArray(): void
    {
        $account = new Account("test666@example.com", "password", $this->buyTransaction->getCurrency(), $this->buyTransaction->getAccount()->getTimeZone(), ['ROLE_USER']);
        $accountingMovements = $this->repo->findByAccountAndYear($account, 2021, 0, 10);
        $this->assertIsArray($accountingMovements);
        $this->assertCount(0, $accountingMovements);
    }

    public function testFindYearOfOldestMovementByAccount(): void
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $oldestYear = $this->repo->findYearOfOldestMovementByAccount($this->buyTransaction->getAccount());
        $this->assertIsInt($oldestYear);
        $this->assertSame((int) $now->format('Y'), $oldestYear);

        $sellDate = new \DateTime('2021-10-01 09:00:00', new \DateTimeZone('UTC'));
        $sellTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, $sellDate, 100, $this->expenses, $this->account);
        $sellTransaction = $this->repoTrans->add($sellTransaction, $this->repo);
        $this->tearDownTrans[] = $sellTransaction;
        $oldestYear = $this->repo->findYearOfOldestMovementByAccount($sellTransaction->getAccount());
        $this->assertIsInt($oldestYear);
        $this->assertSame((int) $sellDate->format('Y'), $oldestYear);
    }

    public function testFindYearOfOldestMovementByAccountWithNoMovementsReturnsCurrentYear(): void
    {
        $account = new Account("test666@example.com", "password", $this->buyTransaction->getCurrency(), $this->buyTransaction->getAccount()->getTimeZone(), ['ROLE_USER']);
        $oldestYear = $this->repo->findYearOfOldestMovementByAccount($account);
        $dateTime = new \DateTime('now', $this->account->getTimeZone());
        $this->assertIsInt($oldestYear);
        $this->assertSame((int) $dateTime->format('Y'), $oldestYear);
    }

    public function testFindTotalPurchaseAndSaleByAccount(): void
    {
        $this->stock->setPrice(new StockPriceVO('5.6633', $this->account->getCurrency()));
        $sellTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('now', new \DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $sellTransaction = $this->repoTrans->add($sellTransaction, $this->repo);
        $this->tearDownTrans[] = $sellTransaction;
        $result = $this->repo->findTotalPurchaseAndSaleByAccount($this->buyTransaction->getAccount());
        $this->assertIsArray($result);
        $this->assertSame('256.2000', $result['buy']);
        $this->assertSame('566.3300', $result['sell']);
    }
}