<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\AccountingMovement;

use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @covers xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryTrait
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory
 */
class AccountingMovementFifoContractInMemoryTest extends AccountingMovementFifoContractTestAbstract
{
    public function setUp(): void
    {
        $this->repoAccountingMovement = new AccountingMovementRepositoryInMemory();
        $this->repoTrans = new TransactionRepositoryInMemory();
        $currency = new Currency('EUR', '€', 2);
        $this->account = new Account('test@example.com', 'password1', $currency, new \DateTimeZone("Europe/Madrid"), ['ROLE_USER']);
        $price = new StockPriceVO('3.5620', $currency);
        $this->stock = new Stock('SAN', 'Santander', $price);
        $this->expenses = new MoneyVO('4.55', $this->account->getCurrency());
    }
}