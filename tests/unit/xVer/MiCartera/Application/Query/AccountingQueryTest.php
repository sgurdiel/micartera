<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Application\Query;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Application\Query\AccountingQuery;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\AccountingMovement\AccountingDTO;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Application\Query\AccountingQuery
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\AccountingMovement\AccountingDTO
 * @uses xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory
 */
class AccountingQueryTest extends TestCase
{
    public function testAccountingQueryCommandSucceeds(): void
    {
        $repo = new TransactionRepositoryInMemory();
        $accountingMovementRepo = new AccountingMovementRepositoryInMemory();
        $currency = new Currency('EUR', '€', 2);
        $email = "test@example.com";
        $password = "password";
        $account = new Account($email, $password, $currency, new \DateTimeZone("Europe/Madrid"), ['ROLE_USER']);
        $price = new StockPriceVO('2.66', $account->getCurrency());
        $stock = new Stock('CABK', "Caixa bank", $price);
        $expenses = new MoneyVO('10.43', $account->getCurrency());
        $buyTransaction = new Transaction(Transaction::TYPE_BUY, $stock, new \DateTime('2021-11-31 23:00:00', new \DateTimeZone('UTC')), 1000, $expenses, $account);
        $buyTransaction = $repo->add($buyTransaction, $accountingMovementRepo);
        $price2 = new StockPriceVO('2.76', $account->getCurrency());
        $stock->setPrice($price2);
        $expenses2 = new MoneyVO('11.43', $account->getCurrency());
        $sellTransaction1 = new Transaction(Transaction::TYPE_SELL, $stock, new \DateTime('2021-12-30 23:00:02', new \DateTimeZone('UTC')), 400, $expenses2, $account);
        $sellTransaction1 = $repo->add($sellTransaction1, $accountingMovementRepo);
        $price3 = new StockPriceVO('1.96', $account->getCurrency());
        $stock->setPrice($price3);
        $sellTransaction2 = new Transaction(Transaction::TYPE_SELL, $stock, new \DateTime('2022-01-31 23:00:02', new \DateTimeZone('UTC')), 200, $expenses2, $account);
        $sellTransaction2 = $repo->add($sellTransaction2, $accountingMovementRepo);


        $command = new AccountingQuery();
        $accountingDTO = $command->execute($accountingMovementRepo, $account, 2022);
        $this->assertInstanceOf(AccountingDTO::class, $accountingDTO);
        $this->assertIsArray($accountingDTO->getAccountingMovements());
        $this->assertCount(1, $accountingDTO->getAccountingMovements());
        $this->assertInstanceOf(AccountingMovement::class, $accountingDTO->getAccountingMovements()[0]);
        $this->assertSame($account, $accountingDTO->getAccount());
        $this->assertTrue($accountingDTO->getAccountingMovements()[0]->getBuyTransaction()->sameId($buyTransaction));
        $this->assertTrue($accountingDTO->getAccountingMovements()[0]->getSellTransaction()->sameId($sellTransaction2));
        $this->assertSame('532.0000', $accountingDTO->getPurchasePrice(0)->getValue());
        $this->assertSame('392.0000', $accountingDTO->getSoldPrice(0)->getValue());
        $this->assertSame('-140.00', $accountingDTO->getProfitPrice(0)->getValue());
        $this->assertSame('-26.32', $accountingDTO->getProfitPercentage(0));   
        $this->assertSame('532.00', $accountingDTO->getYearPurchasePrice()->getValue());
        $this->assertSame('392.00', $accountingDTO->getYearSoldPrice()->getValue());
        $this->assertSame('-140.00', $accountingDTO->getYearForecastProfitPrice()->getValue());
        $this->assertSame('-26.32', $accountingDTO->getYearForecastProfitPercentage());
        $this->assertSame('1596.00', $accountingDTO->getTotalPurchasePrice()->getValue());
        $this->assertSame('1496.00', $accountingDTO->getTotalSoldPrice()->getValue());
        $this->assertSame('-100.00', $accountingDTO->getTotalProfitPrice()->getValue());
        $this->assertSame('-6.27', $accountingDTO->getTotalProfitPercentage());
        $this->assertSame(2021, $accountingDTO->getOldestYear());
        $this->assertSame(2022, $accountingDTO->getDisplayedYear());
        $dateTime = new \DateTime('now', $account->getTimeZone());
        $this->assertSame((int) $dateTime->format('Y'), $accountingDTO->getCurrentYear());
    }
}
