<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\Transaction;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInterface;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

abstract class TransactionRepositoryTestAbstract extends KernelTestCase implements TransactionRepositoryTestInterface
{
    protected TransactionRepositoryInterface $repo;
    protected AccountingMovementRepositoryInterface $accountingMovementRepo;
    protected Account $account;
    protected Account $account2;
    protected Stock $stock;
    protected Stock $stock2;
    protected Stock $stock3;
    protected static \DateTimeZone $timezone;
    protected MoneyVO $expenses;
    protected Currency $currency;
    protected array $tearDownTrans;

    public function testBuyTransactionIsCreatedAndUpdatedAndRemoved(): void
    {
        $amount = 399;
        $transaction = new Transaction(
            Transaction::TYPE_BUY, $this->stock, new \DateTime('2021-09-21 09:44:12', new \DateTimeZone('UTC')), $amount, $this->expenses, $this->account);
        $transaction = $this->repo->add($transaction, $this->accountingMovementRepo);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($this->stock->getPrice(), $transaction->getPrice());
        $this->assertSame($amount, $transaction->getAmount());

        $transactionId = $transaction->getId();
        $trans = $this->repo->findById($transactionId);
        $this->assertInstanceOf(Transaction::class, $trans);
        $this->assertEquals($transactionId, $trans->getId());

        $newPrice = new StockPriceVO('2.7400', $this->currency);
        $newExpenses = new MoneyVO('9.45', $this->currency);
        $trans->setPrice($newPrice);
        $trans->setExpenses($newExpenses);
        $this->repo->update($trans);
        $transaction = $this->repo->findById($transaction->getId());
        $this->assertEquals($newPrice, $transaction->getPrice());
        $this->assertEquals($newExpenses, $transaction->getExpenses());

        $this->repo->remove($transaction, $this->accountingMovementRepo);
        $transaction = $this->repo->findById($transactionId);
        $this->assertSame(null, $transaction);
    }

    public function testfindByIdOrThrowExceptionWithNonExistingThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('expectedPersistedObjectNotFound');
        $uuid = Uuid::v4();
        $this->repo->findByIdOrThrowException($uuid);
    }

    public function testTransactionsAreFoundByStockId(): void
    {
        $transaction = new Transaction(
            Transaction::TYPE_BUY, $this->stock, new \DateTime('2021-10-21 09:44:12', new \DateTimeZone('UTC')), 654, $this->expenses, $this->account);
        $transaction = $this->repo->add($transaction, $this->accountingMovementRepo);
        $this->tearDownTrans[] = $transaction;
        $transactions = $this->repo->findByStockId($this->stock, 20, 0);
        $this->assertCount(2, $transactions);
        foreach ($transactions as $trans) {
            $this->assertInstanceOf(Transaction::class, $trans);
            $this->assertSame($this->stock->getId(), $trans->getStock()->getId());
        }
        $transactions = $this->repo->findByStockId($this->stock, 1, 0);
        $this->assertCount(1, $transactions);
    }

    public function testTransactionsAreFoundByAccount(): void
    {
        $transaction = new Transaction(
            Transaction::TYPE_BUY, $this->stock, new \DateTime('2021-10-21 09:44:12', new \DateTimeZone('UTC')), 654, $this->expenses, $this->account);
        $transaction = $this->repo->add($transaction, $this->accountingMovementRepo);
        $this->tearDownTrans[] = $transaction;

        $transactions = $this->repo->findByAccount($transaction->getAccount(), 10, 0, 'datetimeutc', 'DESC');
        $this->assertCount(2, $transactions);
        foreach ($transactions as $trans) {
            $this->assertInstanceOf(Transaction::class, $trans);
            $this->assertEquals($transaction->getAccount()->getId(), $trans->getAccount()->getId());
        }
        $this->assertEquals(new \DateTime('2021-10-21 09:44:12', new \DateTimeZone('UTC')), $transactions[0]->getDateTimeUtc());
        $transactions = $this->repo->findByAccount($transaction->getAccount(), 10, 0, 'datetimeutc', 'ASC');
        $this->assertCount(2, $transactions);
        foreach ($transactions as $trans) {
            $this->assertInstanceOf(Transaction::class, $trans);
            $this->assertEquals($transaction->getAccount()->getId(), $trans->getAccount()->getId());
        }
        $this->assertEquals(new \DateTime('2021-09-20 12:09:03', new \DateTimeZone('UTC')), $transactions[0]->getDateTimeUtc());

        $transactions = $this->repo->findByAccount($transaction->getAccount(), 10, 0, 'amount', 'DESC');
        $this->assertCount(2, $transactions);
        foreach ($transactions as $trans) {
            $this->assertInstanceOf(Transaction::class, $trans);
            $this->assertEquals($transaction->getAccount()->getId(), $trans->getAccount()->getId());
        }
        $this->assertEquals(new \DateTime('2021-10-21 09:44:12', new \DateTimeZone('UTC')), $transactions[0]->getDateTimeUtc());
        $transactions = $this->repo->findByAccount($transaction->getAccount(), 10, 0, 'amount', 'ASC');
        $this->assertCount(2, $transactions);
        foreach ($transactions as $trans) {
            $this->assertInstanceOf(Transaction::class, $trans);
            $this->assertEquals($transaction->getAccount()->getId(), $trans->getAccount()->getId());
        }
        $this->assertEquals(new \DateTime('2021-09-20 12:09:03', new \DateTimeZone('UTC')), $transactions[0]->getDateTimeUtc());

        $transactions = $this->repo->findByAccount($transaction->getAccount(), 1, 0, null, null);
        $this->assertCount(1, $transactions);
    }

    public function testFindByAccountWithNonExistentAccountReturnsEmptyArray(): void
    {
        $currency = new Currency('EUR', '€', 2);
        $account = new Account("tes2@tt.es", 'adasdasd', $currency, new \DateTimeZone("Europe/Madrid"), ['ROLE_USER']);
        $transactions = $this->repo->findByAccount($account, 10, 0, 'datetimeutc', 'DESC');
        $this->assertIsArray($transactions);
        $this->assertCount(0, $transactions);
    }

    public function testFindByStockIdWithNonExistentStockReturnsEmptyArray(): void
    {
        $price = new StockPriceVO('5.3311', $this->currency);
        $stock = new Stock('UYU', 'UYU Name', $price);
        $transactions = $this->repo->findByStockId($stock, 2, 0);
        $this->assertIsArray($transactions);
        $this->assertCount(0, $transactions);
    }

    public function testMultipleTransWithSameAccountStockTypeOnDateTimeThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transExistsOnDateTime');
        $transaction = new Transaction(
            Transaction::TYPE_BUY, $this->stock, new \DateTime('2022-01-20 16:09:03', new \DateTimeZone('UTC')), 544, $this->expenses, $this->account);
        $this->repo->add($transaction, $this->accountingMovementRepo);
        $this->tearDownTrans[] = $transaction;
        $transaction2 = clone $transaction;
        $transaction2->setExpenses(new MoneyVO('9.99', $this->currency));
        $this->repo->add($transaction2, $this->accountingMovementRepo);
    }

    public function testSellTransactionWithNoAmountOutStandingThrowsException(): void
    {
        $exceptionsThrown = 0;
        $exceptionsMessagesCorrect = 0;
        //Test no buy trans before sell date
        try {
            $transaction = new Transaction(
                Transaction::TYPE_SELL, 
                $this->stock, new \DateTime('2021-09-19 12:09:03', new \DateTimeZone('UTC')), 200, $this->expenses, $this->account);
            $this->repo->add($transaction, $this->accountingMovementRepo);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }
        //Test selling amount exceeds amount outstanding
        try {
            $transaction = new Transaction(
                Transaction::TYPE_SELL, 
                $this->stock, new \DateTime('2021-09-21 12:09:03', new \DateTimeZone('UTC')), 2000, $this->expenses, $this->account);
            $this->repo->add($transaction, $this->accountingMovementRepo);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }
        //Test selling stock has no amount outstanding
        try {
            $transaction = new Transaction(
                Transaction::TYPE_SELL, 
                $this->stock2, new \DateTime('2021-09-02 10:03:05', new \DateTimeZone('UTC')), 1000, $this->expenses, $this->account);
            $this->repo->add($transaction, $this->accountingMovementRepo);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }
        //Test selling account has no amount outstanding
        try {
            $transaction = new Transaction(
                Transaction::TYPE_SELL, 
                $this->stock, new \DateTime('2021-09-02 10:04:02', new \DateTimeZone('UTC')), 200, $this->expenses, $this->account2);
            $this->repo->add($transaction, $this->accountingMovementRepo);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }
        //Test fifo rearrengament produces no amount outstanding
        try {
            $transaction = new Transaction(
                Transaction::TYPE_SELL, 
                $this->stock, new \DateTime('2021-09-02 11:03:02', new \DateTimeZone('UTC')), 600, $this->expenses, $this->account);
            $this->repo->add($transaction, $this->accountingMovementRepo);
            $transaction = new Transaction(
                Transaction::TYPE_SELL, 
                $this->stock, new \DateTime('2021-09-01 17:03:02', new \DateTimeZone('UTC')), 2000, $this->expenses, $this->account);
            $this->repo->add($transaction, $this->accountingMovementRepo);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }
        $this->assertSame(5, $exceptionsThrown);
        $this->assertSame(5, $exceptionsMessagesCorrect);
    }

    public function testSellTransactionIsCreatedAndRemoved(): void
    {       
        $buyTransaction1 = new Transaction(Transaction::TYPE_BUY, $this->stock2, new \DateTime('2021-09-01 10:03:02', new \DateTimeZone('UTC')), 200, $this->expenses, $this->account);
        $buyTransaction1 = $this->repo->add($buyTransaction1, $this->accountingMovementRepo);
        $buyTransaction2 = new Transaction(Transaction::TYPE_BUY, $this->stock2, new \DateTime('2021-09-02 10:03:03', new \DateTimeZone('UTC')), 1000, $this->expenses, $this->account);
        $buyTransaction2 = $this->repo->add($buyTransaction2, $this->accountingMovementRepo);
        $sellTransaction1 = new Transaction(Transaction::TYPE_SELL, $this->stock2, new \DateTime('2021-09-10 09:43:32', new \DateTimeZone('UTC')), 100, $this->expenses, $this->account);
        $sellTransaction1 = $this->repo->add($sellTransaction1, $this->accountingMovementRepo);
        $sellTransaction2 = new Transaction(Transaction::TYPE_SELL, $this->stock2, new \DateTime('2021-09-11 09:43:32', new \DateTimeZone('UTC')), 200, $this->expenses, $this->account);
        $sellTransaction2 = $this->repo->add($sellTransaction2, $this->accountingMovementRepo);
        $transaction = $this->repo->findById($sellTransaction1->getId());
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($sellTransaction1, $transaction);
        $this->assertSame(100, $this->accountingMovementRepo->findByBuyAndSellTransactionIds($buyTransaction1->getId(), $sellTransaction2->getId())->getAmount());
        $this->repo->remove($transaction, $this->accountingMovementRepo);
        $transaction = $this->repo->findById($sellTransaction1->getId());
        $this->assertNull($transaction);
        $this->assertSame(200, $this->accountingMovementRepo->findByBuyAndSellTransactionIds($buyTransaction1->getId(), $sellTransaction2->getId())->getAmount());
        $this->repo->remove($sellTransaction2, $this->accountingMovementRepo);
        $this->repo->remove($buyTransaction1, $this->accountingMovementRepo);
        $this->repo->remove($buyTransaction2, $this->accountingMovementRepo);
    }

    public function testBuyTransactionRemovalThrowsExceptionWhenNotFullAmountOutstanding(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transBuyCannotBeRemovedWithoutFullAmountOutstanding');
        $transaction = new Transaction(
            Transaction::TYPE_BUY, 
            $this->stock3, new \DateTime('2021-09-22 16:11:23', new \DateTimeZone('UTC')), 1500, $this->expenses, $this->account);
        $transactionBuy = $this->repo->add($transaction, $this->accountingMovementRepo);
        $transaction = new Transaction(       
            Transaction::TYPE_SELL, 
            $this->stock3, new \DateTime('2021-09-22 16:11:24', new \DateTimeZone('UTC')), 420, $this->expenses, $this->account);
        $transactionSell = $this->repo->add($transaction, $this->accountingMovementRepo);
        $this->tearDownTrans[] = $transactionSell;
        $this->tearDownTrans[] = $transactionBuy;
        $this->repo->remove($transactionBuy, $this->accountingMovementRepo);
    }

    public function testBuyTransactionsAreFoundByAccountWithAmountOutstanding(): void
    {       
        $transaction1 = new Transaction(Transaction::TYPE_BUY, $this->stock2, new \DateTime('2021-09-21 09:44:12', new \DateTimeZone('UTC')), 440, $this->expenses, $this->account2);
        $transaction1 = $this->repo->add($transaction1, $this->accountingMovementRepo);
        $transaction2 = new Transaction(Transaction::TYPE_BUY, $this->stock2, new \DateTime('2021-09-23 10:51:21s', new \DateTimeZone('UTC')), 600, $this->expenses, $this->account2);
        $transaction2 = $this->repo->add($transaction2, $this->accountingMovementRepo);
        $transactions = $this->repo->findBuyTransactionsByAccountWithAmountOutstanding($this->account2, 'ASC', 'datetimeutc', null, 0);
        $this->assertCount(2, $transactions);
        $this->assertInstanceOf(Transaction::class, $transactions[0]);
        $this->assertInstanceOf(Transaction::class, $transactions[1]);
        $this->assertEquals($transaction1->getId(), $transactions[0]->getId());
        $this->assertEquals($transaction2->getId(), $transactions[1]->getId());
        $transactions = $this->repo->findBuyTransactionsByAccountWithAmountOutstanding($this->account2, 'DESC', 'datetimeutc', null, 0);
        $this->assertCount(2, $transactions);
        $this->assertInstanceOf(Transaction::class, $transactions[0]);
        $this->assertInstanceOf(Transaction::class, $transactions[1]);
        $this->assertEquals($transaction2->getId(), $transactions[0]->getId());
        $this->assertEquals($transaction1->getId(), $transactions[1]->getId());
        $this->tearDownTrans[] = $transaction1;
        $this->tearDownTrans[] = $transaction2;

        $transactions = $this->repo->findBuyTransactionsByAccountWithAmountOutstanding($this->account2, 'ASC', 'datetimeutc', 1, 0);
        $this->assertCount(1, $transactions);
    }

    //public function testBuyTransactionRemovalWhenNotFullAmountOutstandingThrowsException(): void
    //{
    //}

    //public function testFindBuyTransForAccountAndStockWithAmountOutstandingBeforeDate(): void
    //{
    //}
}