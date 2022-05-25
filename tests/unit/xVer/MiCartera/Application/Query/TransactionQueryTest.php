<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Application\Query;

use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Application\Query\QueryResponse;
use xVer\MiCartera\Application\Query\TransactionQuery;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Application\Query\TransactionQuery
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses xVer\Bundle\DomainBundle\Application\Query\QueryResponse
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory
 */
class TransactionQueryTest extends TestCase
{
    public function testQueryCommandSucceeds(): void
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
        $transaction = new Transaction(Transaction::TYPE_BUY, $stock, new \DateTime('2 days ago'), 1000, $expenses, $account);
        $transaction = $repo->add($transaction, $accountingMovementRepo);
        $price2 = new StockPriceVO('2.76', $account->getCurrency());
        $stock->setPrice($price2);
        $expenses2 = new MoneyVO('11.43', $account->getCurrency());
        $transaction2 = new Transaction(Transaction::TYPE_BUY, $stock, new \DateTime('3 days ago'), 1400, $expenses2, $account);
        $transaction2 = $repo->add($transaction2, $accountingMovementRepo);

        $command = new TransactionQuery();
        $queryResponse = $command->execute($repo, $account, 'datetimeutc', 'ASC', 10, 0);
        $this->assertInstanceOf(QueryResponse::class, $queryResponse);
        $this->assertIsArray($queryResponse->getObjects());
        $this->assertCount(2, $queryResponse->getObjects());
        $this->assertInstanceOf(Transaction::class, $queryResponse->getObjects()[0]);
        $this->assertIsBool($queryResponse->getNextPage());
        $this->assertIsBool($queryResponse->getPrevPage());
        $this->assertIsInt($queryResponse->getPage());
        $account2 = new Account("test2@axample.com", "password", $currency, new \DateTimeZone("Europe/Madrid"), ['ROLE_USER']);
        $queryResponse = $command->execute($repo, $account2, 'date', 'ASC', 10, 0);
        $this->assertInstanceOf(QueryResponse::class, $queryResponse);
        $this->assertIsArray($queryResponse->getObjects());
        $this->assertCount(0, $queryResponse->getObjects());
        $this->assertIsBool($queryResponse->getNextPage());
        $this->assertIsBool($queryResponse->getPrevPage());
        $this->assertIsInt($queryResponse->getPage());
    }
}
