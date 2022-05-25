<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\AccountingMovement;

use Tests\unit\xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryTestAbstract;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryTrait
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 */
class AccountingMovementRepositoryInMemoryTest extends AccountingMovementRepositoryTestAbstract
{
    public function setUp(): void
    {
        $this->repo = new AccountingMovementRepositoryInMemory();
        $this->repoTrans = new TransactionRepositoryInMemory();
        $currency = new Currency('EUR', '€', 2);
        $this->account = new Account('test@example.com', 'password1', $currency, new \DateTimeZone("Europe/Madrid"), ['ROLE_USER']);
        $price = new StockPriceVO('2.5620', $currency);
        $this->stock = new Stock('CABK', "Caixa bank", $price);
        $this->expenses = new MoneyVO('11.43', $this->account->getCurrency());
        $this->buyTransaction = new Transaction(
            Transaction::TYPE_BUY, $this->stock, new \DateTime('2021-09-20 12:09:03', new \DateTimeZone('UTC')), 200, $this->expenses, $this->account);
        $this->buyTransaction = $this->repoTrans->add($this->buyTransaction, $this->repo);
    }
}