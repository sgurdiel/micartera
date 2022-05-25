<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Application\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use xVer\MiCartera\Application\Command\AddTransactionCommand;
use xVer\MiCartera\Application\Command\RemoveTransactionCommand;
use xVer\MiCartera\Application\Command\UpdateTransactionCommand;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Application\Command\AddTransactionCommand
 * @covers xVer\MiCartera\Application\Command\RemoveTransactionCommand
 * @covers xVer\MiCartera\Application\Command\UpdateTransactionCommand
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses Symfony\Component\Uid\Uuid
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory
 */
class TransactionCommandTest extends TestCase
{
    private TransactionRepositoryInMemory $repo;
    private AccountingMovementRepositoryInMemory $accountingMovementRepo;
    private Stock $stock;
    private Account $account;
    private static \DateTimeZone $timezone;
    private Uuid $transUuid;

    public static function setUpBeforeClass(): void
    {
        self::$timezone = new \DateTimeZone("Europe/Madrid");
    }

    public function setUp(): void
    {
        $this->repo = new TransactionRepositoryInMemory();
        $this->accountingMovementRepo = new AccountingMovementRepositoryInMemory();
        $currency = new Currency('EUR', '€', 2);
        $email = "test@example.com";
        $password = "password";
        $this->account = new Account($email, $password, $currency, self::$timezone, ['ROLE_USER']);
        $price = new StockPriceVO('2.6500', $this->account->getCurrency());
        $this->stock = new Stock('CABK', 'Caixabank', $price);
        $expenses = new MoneyVO('11.11', $this->account->getCurrency());
        $transaction = new Transaction(Transaction::TYPE_BUY, $this->stock, new \DateTime('2021-05-21 09:44:33', new \DateTimeZone('UTC')), 230, $expenses, $this->account);
        $transaction = $this->repo->add($transaction, $this->accountingMovementRepo);
        $this->transUuid = $transaction->getId();
    }

    public function testAddCommandBuyTransaction(): void
    {
        $currency = new Currency('EUR', '€', 2);
        $email = "test@example.com";
        $password = "password";
        $auxAccount = new Account($email, $password, $currency, self::$timezone, ['ROLE_USER']);
        $price = new StockPriceVO('2.65', $auxAccount->getCurrency());
        $auxStock = new Stock('CABK', 'Caixabank', $price);
        $expenses = new MoneyVO('11.22', $auxAccount->getCurrency());
        $transaction = new Transaction(Transaction::TYPE_BUY, $auxStock, new \DateTime(), 230, $expenses, $auxAccount);
        $command = new AddTransactionCommand();
        $transaction = $command->execute($this->repo, $transaction, $this->accountingMovementRepo);
        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    public function testUpdateCommandSucceeds(): void
    {
        $transaction = $this->repo->findById($this->transUuid);
        $currency = new Currency('EUR', '€', 2);
        $price = new StockPriceVO('3.1533', $currency);
        $transaction->setPrice($price);
        $expenses = new MoneyVO('3.23', $currency);
        $transaction->setExpenses($expenses);
        $command = new UpdateTransactionCommand();
        $command->execute($this->repo, $transaction);
        $transaction = $this->repo->findById($this->transUuid);
        $this->assertEquals($price, $transaction->getPrice());
        $this->assertEquals($expenses, $transaction->getExpenses());
    }

    public function testMultipleTransWithSameAccountStockTypeOnDateTimeThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $expenses = new MoneyVO('10.43', $this->account->getCurrency());
        $transaction = new Transaction(
            Transaction::TYPE_BUY, $this->stock, new \DateTime('2021-05-21 09:44:33', new \DateTimeZone('UTC')), 230, $expenses, $this->account);
        $command = new AddTransactionCommand();
        $transaction = $command->execute($this->repo, $transaction, $this->accountingMovementRepo);
    }

    public function testSellTransactionWithNoAmountOutStandingThrowsException(): void
    {
        $exceptionsThrown = 0;
        $exceptionsMessagesCorrect = 0;
        //Test no buy trans before sell date
        try {
            $expenses = new MoneyVO('11.13', $this->account->getCurrency());
            $transaction = new Transaction(
                Transaction::TYPE_SELL, 
                $this->stock, new \DateTime('2021-05-20 09:44:33', new \DateTimeZone('UTC')), 230, $expenses, $this->account
            );
            $command = new AddTransactionCommand();
            $transaction = $command->execute($this->repo, $transaction, $this->accountingMovementRepo);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }

        //Test selling amount exceeds amount outstanding
        try {
            $expenses = new MoneyVO('11.14', $this->account->getCurrency());
            $transaction = new Transaction(
                Transaction::TYPE_SELL, 
                $this->stock, new \DateTime('2021-05-22 09:44:33', new \DateTimeZone('UTC')), 460, $expenses, $this->account
            );
            $command = new AddTransactionCommand();
            $transaction = $command->execute($this->repo, $transaction, $this->accountingMovementRepo);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }
        
        //Test selling stock has no amount outstanding
        try {
            $price = new StockPriceVO('4.23', $this->account->getCurrency());
            $auxStock = new Stock('TEF', 'Telefonia', $price); 
            $expenses = new MoneyVO('11.17', $this->account->getCurrency());
            $transaction = new Transaction(
                Transaction::TYPE_SELL, $auxStock, new \DateTime('2021-05-22 09:44:34', new \DateTimeZone('UTC')), 230, $expenses, $this->account);
            $command = new AddTransactionCommand();
            $transaction = $command->execute($this->repo, $transaction, $this->accountingMovementRepo);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }

        //Test selling account has no amount outstanding
        try {
            $auxAccount = new Account('test4@example.com', 'password', $this->account->getCurrency(), self::$timezone, ['ROLE_USER']);
            $expenses = new MoneyVO('11.52', $this->account->getCurrency());
            $transaction = new Transaction(
                Transaction::TYPE_SELL, $this->stock, new \DateTime('2021-05-22 09:44:53', new \DateTimeZone('UTC')), 230, $expenses, $auxAccount);
            $command = new AddTransactionCommand();
            $transaction = $command->execute($this->repo, $transaction, $this->accountingMovementRepo);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }

        //Test fifo rearrengament produces no amount outstanding
        try {
            $expenses = new MoneyVO('11.12', $this->account->getCurrency());
            $transaction = new Transaction(
                Transaction::TYPE_SELL, $this->stock, new \DateTime('2021-09-02 10:03:02', new \DateTimeZone('UTC')), 600, $expenses, $this->account);
            $command = new AddTransactionCommand();
            $transaction = $command->execute($this->repo, $transaction, $this->accountingMovementRepo);
            $transaction = new Transaction(
                Transaction::TYPE_SELL, $this->stock, new \DateTime('2021-09-01 17:03:02', new \DateTimeZone('UTC')), 2000, $expenses, $this->account);
            $command = new AddTransactionCommand();
            $transaction = $command->execute($this->repo, $transaction, $this->accountingMovementRepo);
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
        }
        $this->assertSame(5, $exceptionsThrown);
        $this->assertSame(5, $exceptionsMessagesCorrect);
    }

    public function testAddRemoveCommandSellSucceeds(): void
    {
        $transaction1 = $this->repo->findById($this->transUuid);
        $expenses = new MoneyVO('8.12', $this->account->getCurrency());
        $price = new StockPriceVO('2.46', $this->account->getCurrency());       
        $this->stock->setPrice($price);
        $transaction2 = new Transaction(Transaction::TYPE_BUY, $this->stock, new \DateTime('2021-09-10 12:53:22', new \DateTimeZone('UTC')), 600, $expenses, $this->account);
        $command = new AddTransactionCommand();
        $transaction2 = $command->execute($this->repo, $transaction2, $this->accountingMovementRepo);
        $this->assertSame(230, $transaction1->getAmountOutstanding());
        $this->assertSame(600, $transaction2->getAmountOutstanding());

        $expenses = new MoneyVO('3.42', $this->account->getCurrency());
        $transaction3 = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('2021-06-03 09:43:32', new \DateTimeZone('UTC')), 200, $expenses, $this->account);
        $command = new AddTransactionCommand();
        $transaction3 = $command->execute($this->repo, $transaction3, $this->accountingMovementRepo);
        $this->assertSame(30, $transaction1->getAmountOutstanding());
        $this->assertSame(600, $transaction2->getAmountOutstanding());

        $expenses = new MoneyVO('3.82', $this->account->getCurrency());
        $transaction4 = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('2021-09-12 11:13:32', new \DateTimeZone('UTC')), 100, $expenses, $this->account);
        $command = new AddTransactionCommand();
        $transaction4 = $command->execute($this->repo, $transaction4, $this->accountingMovementRepo);
        $this->assertSame(0, $transaction1->getAmountOutstanding());
        $this->assertSame(530, $transaction2->getAmountOutstanding());

        $expenses = new MoneyVO('3.82', $this->account->getCurrency());
        $transaction5 = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('2021-09-14 15:31:32', new \DateTimeZone('UTC')), 400, $expenses, $this->account);
        $command = new AddTransactionCommand();
        $transaction5 = $command->execute($this->repo, $transaction5, $this->accountingMovementRepo);
        $this->assertSame(0, $transaction1->getAmountOutstanding());
        $this->assertSame(130, $transaction2->getAmountOutstanding());

        $command = new RemoveTransactionCommand();
        $transaction5 = $command->execute($this->repo, $transaction5, $this->accountingMovementRepo);
        $this->assertSame(0, $transaction1->getAmountOutstanding());
        $this->assertSame(530, $transaction2->getAmountOutstanding());
        $command = new RemoveTransactionCommand();
        $transaction4 = $command->execute($this->repo, $transaction4, $this->accountingMovementRepo);
        $this->assertSame(30, $transaction1->getAmountOutstanding());
        $this->assertSame(600, $transaction2->getAmountOutstanding());
        $command = new RemoveTransactionCommand();
        $transaction3 = $command->execute($this->repo, $transaction3, $this->accountingMovementRepo);
        $this->assertSame(230, $transaction1->getAmountOutstanding());
        $this->assertSame(600, $transaction2->getAmountOutstanding());
    }

    public function testRemoveCommandBuyTransaction(): void
    {
        $transaction = $this->repo->findById($this->transUuid);
        $command = new RemoveTransactionCommand();
        $command->execute($this->repo, $transaction, $this->accountingMovementRepo);
        $transaction = $this->repo->findById($this->transUuid);
        $this->assertNull($transaction);
    }

    public function testRemoveCommandBuyTransactionThrowsExceptionWhenNotFullAmountOutstanding(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transBuyCannotBeRemovedWithoutFullAmountOutstanding');
        $expenses = new MoneyVO('3.42', $this->account->getCurrency());
        $transaction = new Transaction(Transaction::TYPE_SELL, $this->stock, new \DateTime('2021-09-3 09:43:32', new \DateTimeZone('UTC')), 100, $expenses, $this->account);
        $command = new AddTransactionCommand();
        $transaction = $command->execute($this->repo, $transaction, $this->accountingMovementRepo);
        $transaction = $this->repo->findById($this->transUuid);
        $command = new RemoveTransactionCommand();
        $command->execute($this->repo, $transaction, $this->accountingMovementRepo);
    }
}
