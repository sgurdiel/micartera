<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\Transaction;

use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 */
class TransactionRepositoryInMemoryTest extends TransactionRepositoryTestAbstract
{    
    public static function setUpBeforeClass(): void
    {
        self::$timezone = new \DateTimeZone("Europe/Madrid");
    }

    public function setUp(): void
    {
        $this->repo = new TransactionRepositoryInMemory();
        $this->accountingMovementRepo = new AccountingMovementRepositoryInMemory();
        $this->currency = new Currency('EUR', '€', 2);
        $this->account = new Account('test@example.com', 'password1', $this->currency, new \DateTimeZone("Europe/Madrid"), ['ROLE_USER']);
        $this->account2 = new Account('test_other@example.com', 'password2', $this->currency, new \DateTimeZone("America/Chicago"), ['ROLE_USER']);
        $price = new StockPriceVO('2.5620', $this->account->getCurrency());
        $this->stock = new Stock('CABK', "Caixa bank", $price);
        $price2 = new StockPriceVO('3.5620', $this->account->getCurrency());
        $this->stock2 = new Stock('SAN', 'Santander Name', $price2);
        $price3 = new StockPriceVO('5.9620', $this->account->getCurrency());
        $this->stock3 = new Stock('ROVI', 'Laboratorios Rovi', $price3);
        $this->expenses = new MoneyVO('11.43', $this->account->getCurrency());
    }

    public function testTransactionsAreFoundByStockId(): void
    {
        $transaction = new Transaction(
            Transaction::TYPE_BUY, $this->stock, new \DateTime('2021-09-20 12:09:03', new \DateTimeZone('UTC')), 200, $this->expenses, $this->account);
        $this->repo->add($transaction, $this->accountingMovementRepo);
        parent::testTransactionsAreFoundByStockId();
    }

    public function testTransactionsAreFoundByAccount(): void
    {
        $transaction = new Transaction(
            Transaction::TYPE_BUY, $this->stock, new \DateTime('2021-09-20 12:09:03', new \DateTimeZone('UTC')), 200, $this->expenses, $this->account);
        $this->repo->add($transaction, $this->accountingMovementRepo);
        parent::testTransactionsAreFoundByAccount();
    }
}