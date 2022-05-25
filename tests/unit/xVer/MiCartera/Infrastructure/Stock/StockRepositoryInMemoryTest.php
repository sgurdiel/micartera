<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\Stock;

use Tests\unit\xVer\MiCartera\Infrastructure\Stock\StockRepositoryTestAbstract;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryInMemory;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Infrastructure\Stock\StockRepositoryInMemory
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryTrait
 */
class StockRepositoryInMemoryTest extends StockRepositoryTestAbstract
{
    protected StockRepositoryInMemory $repo;

    public function setUp(): void
    {
        $this->repo = new StockRepositoryInMemory();
        $this->repoCurrency = new CurrencyRepositoryInMemory();
        $this->currency = new Currency('EUR', '€', 2);
        $this->currency2 = new Currency('USD', '$',2);
        $this->code= 'ABCD';
        $this->name = 'ABCD Name';
        $this->price = new StockPriceVO('2.6632', $this->currency);
        $this->stock = new Stock($this->code, $this->name, $this->price);
    }

    public function testStockIsAddedUpdatedAndRemoved(): void
    {           
        $this->repoTrans = new TransactionRepositoryInMemory();
        parent::testStockIsAddedUpdatedAndRemoved();
    }

    public function testRemovingStockHavingTransactionsThrowsException(): void
    {
        $this->repoTrans = new TransactionRepositoryInMemory();
        $repoAccountingMovement = new AccountingMovementRepositoryInMemory();
        $repoAccount = new AccountRepositoryInMemory();
        $repoCurrency = new CurrencyRepositoryInMemory();
        $currency = $repoCurrency->add($this->currency);
        $price = new StockPriceVO('2.5620', $this->currency);
        $this->code2 = 'CABK';
        $stock = new Stock($this->code2, 'Caixabank', $price);
        $stock = $this->repo->add($stock);
        $expenses = new MoneyVO('5.44', $currency);
        $account = new Account('test3@example.com', 'password', $currency, new \DateTimeZone('UTC'));
        $account = $repoAccount->add($account, $repoCurrency);
        $buyTrans = new Transaction(Transaction::TYPE_BUY, $stock, new \DateTime('now', new \DateTimeZone('UTC')), 400, $expenses, $account);
        $buyTrans = $this->repoTrans->add($buyTrans, $repoAccountingMovement);
        parent::testRemovingStockHavingTransactionsThrowsException();
    }
}
