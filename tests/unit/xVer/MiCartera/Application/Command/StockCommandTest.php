<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Application\Command;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Application\Command\AddStockCommand;
use xVer\MiCartera\Application\Command\RemoveStockCommand;
use xVer\MiCartera\Application\Command\UpdateStockCommand;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Application\Command\AddStockCommand
 * @covers xVer\MiCartera\Application\Command\RemoveStockCommand
 * @covers xVer\MiCartera\Application\Command\UpdateStockCommand
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 */
class StockCommandTest extends TestCase
{
    private StockRepositoryInMemory $repo;
    private Stock $stock;
    private Currency $currency;

    public function setUp(): void
    {
        $this->repo = new StockRepositoryInMemory();
        $this->currency = new Currency('EUR', '€', 2);
        $price = new StockPriceVO('5.3400', $this->currency);
        $this->stock = new Stock('TEF', 'Telefonica', $price);
        $this->repo->add($this->stock);
    }

    public function testAddCommandSucceeds(): void
    {
        $auxCurrency = new Currency('EUR', '€', 2);
        $price = new StockPriceVO('2.6500', $auxCurrency);
        $auxStock = new Stock('CABK', 'Caixabank', $price);
        $command = new AddStockCommand();
        $retStock = $command->execute($this->repo, $auxStock);
        $this->assertInstanceOf(Stock::class, $retStock);
    }

    public function testAddCommandFails(): void
    {
        $this->expectException(DomainException::class);
        $command = new AddStockCommand();
        $command->execute($this->repo, $this->stock);
    }

    public function testUpdateCommandSucceeds(): void
    {
        $newPrice = new StockPriceVO('3.1200', $this->currency);
        $newName = 'Telefonica New';
        $this->stock->setName($newName);
        $this->stock->setPrice($newPrice);
        $command = new UpdateStockCommand();
        $command->execute($this->repo, $this->stock);
        $qStock = $this->repo->findById($this->stock->getId());
        $this->assertSame($newName, $qStock->getName());
        $this->assertEquals($newPrice, $qStock->getPrice());
        $this->assertEquals($this->currency, $qStock->getCurrency());
    }

    public function testRemoveCommandWhenTransactionsExistsWillThrowException(): void
    {
        $this->expectException(DomainException::class);
        $transRepo = new TransactionRepositoryInMemory();
        $accMoveRepo = new AccountingMovementRepositoryInMemory();
        $account = new Account('test@example.com', 'abc', $this->currency, new \DateTimeZone('Europe/Madrid'), ['ROLE_USER']);
        $expenses = new MoneyVO('4.54', $this->currency);
        $transaction = new Transaction(Transaction::TYPE_BUY, $this->stock, new \DateTime('now', new \DateTimeZone('UTC')), 100, $expenses, $account);
        $transaction = $transRepo->add($transaction, $accMoveRepo);
        $command = new RemoveStockCommand();
        $command->execute($this->repo, $this->stock, $transRepo);
    }

    public function testRemoveCommandSucceeds(): void
    {
        $transRepo = new TransactionRepositoryInMemory();
        $command = new RemoveStockCommand();
        $command->execute($this->repo, $this->stock, $transRepo);
        $this->assertNull($this->repo->findById($this->stock->getId()));
    }
}
