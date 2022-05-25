<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\AccountingMovement;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\MiCartera\Domain\Account\Account;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInterface;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInterface;

abstract class AccountingMovementFifoContractTestAbstract extends KernelTestCase implements AccountingMovementFifoContractTestInterface
{
    protected AccountingMovementRepositoryInterface $repoAccountingMovement;
    protected TransactionRepositoryInterface $repoTrans;
    protected Account $account;
    protected Stock $stock;
    protected MoneyVO $expenses;
    protected $buyTransactions = [];
    protected $sellTransactions = [];

    public function testFifoContract(): void
    {
        // Test create buy transaction
        $buyTransaction = new Transaction(Transaction::TYPE_BUY, $this->stock, new \DateTime('2022-03-23 09:00:00', new \DateTimeZone('Europe/Madrid')), 1000, $this->expenses, $this->account);
        $this->repoTrans->add($buyTransaction, $this->repoAccountingMovement);
        /** @var AccountingMovements[] $persistedAccountingMovements */
        $persistedAccountingMovements = $this->repoAccountingMovement->findByAccountStockBuyDateAfter($this->account, $this->stock, new \DateTime('1990-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->assertCount(0, $persistedAccountingMovements);
        $this->buyTransactions[0] = $buyTransaction;
    
        // Test create sell transaction
        $sellTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('2022-03-23 10:00:00', new \DateTimeZone('Europe/Madrid')), 500, $this->expenses, $this->account);
        $this->repoTrans->add($sellTransaction, $this->repoAccountingMovement);
        /** @var AccountingMovements[] $persistedAccountingMovements */
        $persistedAccountingMovements = $this->repoAccountingMovement->findByAccountStockBuyDateAfter($this->account, $this->stock, new \DateTime('1990-01-01 00:00:00', new \DateTimeZone('UTC')));
        $expectedAccountingMovements = [
            0 => ["buyTrans" => $this->buyTransactions[0], "sellTrans" => $sellTransaction, "amount" => 500]
        ];
        $this->assertCount(count($expectedAccountingMovements), $persistedAccountingMovements);
        $this->assertTrue($this->checkAccountingMovements($expectedAccountingMovements, $persistedAccountingMovements));
        $this->sellTransactions[0] = $sellTransaction;
        
        // Test create buy transaction requiring accounting movement rearrangement
        $buyTransaction = new Transaction(Transaction::TYPE_BUY, $this->stock, new \DateTime('2022-03-22 10:40:00', new \DateTimeZone('Europe/Madrid')), 200, $this->expenses, $this->account);
        $this->repoTrans->add($buyTransaction, $this->repoAccountingMovement);
        /** @var AccountingMovements[] $persistedAccountingMovements */
        $persistedAccountingMovements = $this->repoAccountingMovement->findByAccountStockBuyDateAfter($this->account, $this->stock, new \DateTime('1990-01-01 00:00:00', new \DateTimeZone('UTC')));
        $expectedAccountingMovements = [
            0 => ["buyTrans" => $buyTransaction, "sellTrans" => $this->sellTransactions[0], "amount" => 200],
            1 => ["buyTrans" => $this->buyTransactions[0], "sellTrans" => $this->sellTransactions[0], "amount" => 300]
        ];
        $this->assertCount(count($expectedAccountingMovements), $persistedAccountingMovements);
        $this->assertTrue($this->checkAccountingMovements($expectedAccountingMovements, $persistedAccountingMovements));
        $this->buyTransactions[1] = $buyTransaction;
     
        // Test create sell transaction requiring accounting movement rearrangement
        $sellTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('2022-03-22 10:50:00', new \DateTimeZone('Europe/Madrid')), 100, $this->expenses, $this->account);
        $this->repoTrans->add($sellTransaction, $this->repoAccountingMovement);
        /** @var AccountingMovements[] $persistedAccountingMovements */
        $persistedAccountingMovements = $this->repoAccountingMovement->findByAccountStockBuyDateAfter($this->account, $this->stock, new \DateTime('1990-01-01 00:00:00', new \DateTimeZone('UTC')));
        $expectedAccountingMovements = [
            0 => ["buyTrans" => $this->buyTransactions[1], "sellTrans" => $sellTransaction, "amount" => 100],
            1 => ["buyTrans" => $this->buyTransactions[1], "sellTrans" => $this->sellTransactions[0], "amount" => 100],
            2 => ["buyTrans" => $this->buyTransactions[0], "sellTrans" => $this->sellTransactions[0], "amount" => 400]
        ];
        $this->assertCount(count($expectedAccountingMovements), $persistedAccountingMovements);
        $this->assertTrue($this->checkAccountingMovements($expectedAccountingMovements, $persistedAccountingMovements));
        $this->sellTransactions[1] = $sellTransaction;
   
        // Test create other sell transaction requiring accounting movement rearrangement
        $sellTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('2022-03-23 09:59:59', new \DateTimeZone('Europe/Madrid')), 500, $this->expenses, $this->account);
        $this->repoTrans->add($sellTransaction, $this->repoAccountingMovement);
        /** @var AccountingMovements[] $persistedAccountingMovements */
        $persistedAccountingMovements = $this->repoAccountingMovement->findByAccountStockBuyDateAfter($this->account, $this->stock, new \DateTime('1990-01-01 00:00:00', new \DateTimeZone('UTC')));
        $expectedAccountingMovements = [
            0 => ["buyTrans" => $this->buyTransactions[1], "sellTrans" => $this->sellTransactions[1], "amount" => 100],
            1 => ["buyTrans" => $this->buyTransactions[1], "sellTrans" => $sellTransaction, "amount" => 100],
            2 => ["buyTrans" => $this->buyTransactions[0], "sellTrans" => $sellTransaction, "amount" => 400],
            3 => ["buyTrans" => $this->buyTransactions[0], "sellTrans" => $this->sellTransactions[0], "amount" => 500]
        ];
        $this->assertCount(count($expectedAccountingMovements), $persistedAccountingMovements);
        $this->assertTrue($this->checkAccountingMovements($expectedAccountingMovements, $persistedAccountingMovements));
        $this->sellTransactions[2] = $sellTransaction;        
    
        // Test remove sell transaction
        $this->repoTrans->remove($this->sellTransactions[1], $this->repoAccountingMovement);
        /** @var AccountingMovements[] $persistedAccountingMovements */
        $persistedAccountingMovements = $this->repoAccountingMovement->findByAccountStockBuyDateAfter($this->account, $this->stock, new \DateTime('1990-01-01 00:00:00', new \DateTimeZone('UTC')));
        $expectedAccountingMovements = [
            0 => ["buyTrans" => $this->buyTransactions[1], "sellTrans" => $this->sellTransactions[2], "amount" => 200],
            1 => ["buyTrans" => $this->buyTransactions[0], "sellTrans" => $this->sellTransactions[2], "amount" => 300],
            2 => ["buyTrans" => $this->buyTransactions[0], "sellTrans" => $this->sellTransactions[0], "amount" => 500]
        ];
        $this->assertCount(count($expectedAccountingMovements), $persistedAccountingMovements);
        $this->assertTrue($this->checkAccountingMovements($expectedAccountingMovements, $persistedAccountingMovements));
    
        // Test remove other sell transaction
        $this->repoTrans->remove($this->sellTransactions[0], $this->repoAccountingMovement);
        /** @var AccountingMovements[] $persistedAccountingMovements */
        $persistedAccountingMovements = $this->repoAccountingMovement->findByAccountStockBuyDateAfter($this->account, $this->stock, new \DateTime('1990-01-01 00:00:00', new \DateTimeZone('UTC')));
        $expectedAccountingMovements = [
            0 => ["buyTrans" => $this->buyTransactions[1], "sellTrans" => $this->sellTransactions[2], "amount" => 200],
            1 => ["buyTrans" => $this->buyTransactions[0], "sellTrans" => $this->sellTransactions[2], "amount" => 300]
        ];
        $this->assertCount(count($expectedAccountingMovements), $persistedAccountingMovements);
        $this->assertTrue($this->checkAccountingMovements($expectedAccountingMovements, $persistedAccountingMovements));
    
        // Test add buy transaction not requiring rearrangement
        $buyTransaction = new Transaction(Transaction::TYPE_BUY, $this->stock, new \DateTime('2022-03-25 10:40:00', new \DateTimeZone('Europe/Madrid')), 200, $this->expenses, $this->account);
        $this->repoTrans->add($buyTransaction, $this->repoAccountingMovement);
        /** @var AccountingMovements[] $persistedAccountingMovements */
        $persistedAccountingMovements = $this->repoAccountingMovement->findByAccountStockBuyDateAfter($this->account, $this->stock, new \DateTime('1990-01-01 00:00:00', new \DateTimeZone('UTC')));
        $expectedAccountingMovements = [
            0 => ["buyTrans" => $this->buyTransactions[1], "sellTrans" => $this->sellTransactions[2], "amount" => 200],
            1 => ["buyTrans" => $this->buyTransactions[0], "sellTrans" => $this->sellTransactions[2], "amount" => 300]
        ];
        $this->assertCount(count($expectedAccountingMovements), $persistedAccountingMovements);
        $this->assertTrue($this->checkAccountingMovements($expectedAccountingMovements, $persistedAccountingMovements));
        $this->buyTransactions[2] = $buyTransaction;
    
        // Test adding sell transaction with insufficient amount outstanding throws exception
        $exceptionsThrown = 0;
        $exceptionsMessagesCorrect = 0;
        try {
            $dateTime = clone $this->buyTransactions[1]->getDateTimeUtc();
            $sellTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, $dateTime->sub(new \DateInterval('PT30S')), 1000, $this->expenses, $this->account);
            $this->repoTrans->add($sellTransaction, $this->repoAccountingMovement);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }
        try {
            $dateTime2 = clone  $this->sellTransactions[2]->getDateTimeUtc();
            $sellTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, $dateTime2->sub(new \DateInterval('PT30S')), 1200, $this->expenses, $this->account);
            $this->repoTrans->add($sellTransaction, $this->repoAccountingMovement);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }
        $this->assertSame(2, $exceptionsThrown);
        $this->assertSame(2, $exceptionsMessagesCorrect);

        // Test remove sell transaction not requiring rearrangement
        $this->repoTrans->remove($this->sellTransactions[2], $this->repoAccountingMovement);
        /** @var AccountingMovements[] $persistedAccountingMovements */
        $persistedAccountingMovements = $this->repoAccountingMovement->findByAccountStockBuyDateAfter($this->account, $this->stock, new \DateTime('1990-01-01 00:00:00', new \DateTimeZone('UTC')));
        $expectedAccountingMovements = [];
        $this->assertCount(count($expectedAccountingMovements), $persistedAccountingMovements);
        $this->assertTrue($this->checkAccountingMovements($expectedAccountingMovements, $persistedAccountingMovements));

        // Test accounting movements rearrange causes buy transaction selection with date after sell's
        $this->repoTrans->remove($this->buyTransactions[0], $this->repoAccountingMovement);
        $sellTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('2022-03-23 11:40:00', new \DateTimeZone('Europe/Madrid')), 200, $this->expenses, $this->account);
        $this->repoTrans->add($sellTransaction, $this->repoAccountingMovement);
        $this->sellTransactions[3] = $sellTransaction;
        $sellTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('2022-03-25 11:40:00', new \DateTimeZone('Europe/Madrid')), 200, $this->expenses, $this->account);
        $this->repoTrans->add($sellTransaction, $this->repoAccountingMovement);
        $this->sellTransactions[4] = $sellTransaction;

        $exceptionsThrown = 0;
        $exceptionsMessagesCorrect = 0;
        try {
            $buyTransaction = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('2022-03-22 11:40:00', new \DateTimeZone('Europe/Madrid')), 200, $this->expenses, $this->account);
            $this->repoTrans->add($buyTransaction, $this->repoAccountingMovement);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }
        $this->assertSame(1, $exceptionsThrown);
        $this->assertSame(1, $exceptionsMessagesCorrect);

        //// Clean up
        $this->repoTrans->remove($this->sellTransactions[4], $this->repoAccountingMovement);
        $this->repoTrans->remove($this->sellTransactions[3], $this->repoAccountingMovement);
        $this->repoTrans->remove($this->buyTransactions[2], $this->repoAccountingMovement);
        $this->repoTrans->remove($this->buyTransactions[1], $this->repoAccountingMovement);
    }

    /**
     * @param AccountingMovement[] $persistedAccountingMovements
     */
    private function checkAccountingMovements(array $expectedAccountingMovements, array $persistedAccountingMovements): bool
    {
        $result = true;
        foreach ($expectedAccountingMovements as $expectedAccountingMovement) {
            $found = false;
            foreach ($persistedAccountingMovements as $persistedAccountingMovement) {
                if (
                    $persistedAccountingMovement->getBuyTransaction()->sameId($expectedAccountingMovement['buyTrans'])
                    && $persistedAccountingMovement->getSellTransaction()->sameId($expectedAccountingMovement['sellTrans'])
                    && $persistedAccountingMovement->getAmount() === $expectedAccountingMovement['amount']
                ) {
                    $found = true;
                    break;
                }
            }
            if (false === ($result = $found)) {
                break;
            }
        }
        return $result;
    }
}