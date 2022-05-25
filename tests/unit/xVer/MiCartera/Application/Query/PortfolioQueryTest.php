<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Application\Query;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Application\Query\PortfolioQuery;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Transaction\PortfolioDTO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory;

/**
 * @covers xVer\MiCartera\Application\Query\PortfolioQuery
 * @uses xVer\Bundle\DomainBundle\Infrastructure\PersistanceInMemory
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Transaction\PortfolioDTO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
 * @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryInMemory
 * @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryInMemory
 */
class PortfolioQueryTest extends TestCase
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

        $command = new PortfolioQuery();
        $portfolioDTO = $command->execute($repo, $account);
        $this->assertInstanceOf(PortfolioDTO::class, $portfolioDTO);
        $this->assertIsArray($portfolioDTO->getOutstandingPositions());
        $this->assertCount(2, $portfolioDTO->getOutstandingPositions());
        foreach($portfolioDTO->getOutstandingPositions() as $outstandingPosition){
            $this->assertInstanceOf(Transaction::class, $outstandingPosition);
        }
        $account2 = new Account("test2@axample.com", "password", $currency, new \DateTimeZone("Europe/Madrid"), ['ROLE_USER']);
        $portfolioDTO = $command->execute($repo, $account2);
        $this->assertInstanceOf(PortfolioDTO::class, $portfolioDTO);
        $this->assertIsArray($portfolioDTO->getOutstandingPositions());
        $this->assertCount(0, $portfolioDTO->getOutstandingPositions());
    }
}
