<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Domain\AccountingMovement;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\AccountingMovement\AccountingDTO;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;

/**
 * @covers xVer\MiCartera\Domain\AccountingMovement\AccountingDTO
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 */
class AccountingDTOTest extends TestCase
{
    private static Account $account;
    private static Currency $currency;
    private static Stock $stock;
    /** @var AccountingMovement[] */
    private static array $accountingMovements = [];
    private static int $year;
    private static int $oldestYear;
    private static array $totals;

    public static function setUpBeforeClass(): void
    {
        self::$currency = new Currency('EUR', '€', 2);
        $price = new StockPriceVO('4.5600', self::$currency);
        self::$stock = new Stock('ABCD', 'ABCD Name', $price);
        $expenses = new MoneyVO('3.34', self::$currency);
        self::$account = new Account('test@example.com', 'password', self::$currency, new \DateTimeZone('Europe/Madrid'), ['ROLE_USER']);
        $buyTransaction = new Transaction(Transaction::TYPE_BUY, self::$stock, new \DateTime('2021-11-23 09:00:00', new \DateTimeZone('UTC')), 400, $expenses, self::$account);
        $price = new StockPriceVO('4.9600', self::$currency);
        self::$stock->setPrice($price);
        $expenses = new MoneyVO('3.64', self::$currency);
        $sellTransaction = new Transaction(Transaction::TYPE_SELL, self::$stock, new \DateTime('2021-11-24 10:00:02', new \DateTimeZone('UTC')), 100, $expenses, self::$account);
        $sellTransaction = new Transaction(Transaction::TYPE_SELL, self::$stock, new \DateTime('2022-01-03 09:00:42', new \DateTimeZone('UTC')), 50, $expenses, self::$account);
        self::$accountingMovements[] = new AccountingMovement($buyTransaction, $sellTransaction, 50);
        self::$oldestYear = 2021;
        self::$year = 2022;
        self::$totals = [
            'buy' => '684.00',
            'sell' => '744'
        ];
    }

    public function testAccountingDTO(): void
    {
        $accountingDTO = new AccountingDTO(self::$account, self::$year, self::$oldestYear, self::$accountingMovements, self::$totals);
        $this->assertIsArray($accountingDTO->getAccountingMovements());
        $this->assertCount(1, $accountingDTO->getAccountingMovements());
        $this->assertSame(self::$account, $accountingDTO->getAccount());
        foreach($accountingDTO->getAccountingMovements() as $key => $accountingMovement) {
            $this->assertInstanceOf(AccountingMovement::class, $accountingMovement);
            $this->assertSame(self::$accountingMovements[$key]->getBuyTransaction(), $accountingMovement->getBuyTransaction());
            $this->assertSame(self::$accountingMovements[$key]->getSellTransaction(), $accountingMovement->getSellTransaction());
            $this->assertSame(self::$accountingMovements[$key]->getAmount(), $accountingMovement->getAmount());
            $this->assertSame(
                self::$accountingMovements[$key]->getBuyTransaction()->getPrice()->multiply((string) $accountingMovement->getAmount())->getValue(),
                $accountingDTO->getPurchasePrice($key)->getValue());
            $this->assertSame(
                self::$accountingMovements[$key]->getSellTransaction()->getPrice()->multiply((string) $accountingMovement->getAmount())->getValue(), 
                $accountingDTO->getSoldPrice($key)->getValue());
            $this->assertSame(
                $accountingDTO->getSoldPrice($key)->toMoney()->subtract($accountingDTO->getPurchasePrice($key)->toMoney())->getValue(),
                $accountingDTO->getProfitPrice($key)->getValue()
            );
            $this->assertSame(
                $accountingDTO->getPurchasePrice($key)->toMoney()->percentageDifference($accountingDTO->getSoldPrice($key)->toMoney()),
                $accountingDTO->getProfitPercentage($key)
            );
        }
        $this->assertSame(2021, $accountingDTO->getOldestYear());
        $this->assertSame(self::$year, $accountingDTO->getDisplayedYear());
        $date = new \DateTime("now", self::$account->getTimeZone());
        $this->assertSame((int) $date->format('Y'), $accountingDTO->getCurrentYear());
        $this->assertSame('228.00', $accountingDTO->getYearPurchasePrice()->getValue());
        $this->assertSame('248.00', $accountingDTO->getYearSoldPrice()->getValue());
        $this->assertSame('20.00', $accountingDTO->getYearForecastProfitPrice()->getValue());
        $this->assertSame('8.77', $accountingDTO->getYearForecastProfitPercentage());   
        $this->assertSame('684.00', $accountingDTO->getTotalPurchasePrice()->getValue());
        $this->assertSame('744.00', $accountingDTO->getTotalSoldPrice()->getValue());
        $this->assertSame('60.00', $accountingDTO->getTotalProfitPrice()->getValue());
        $this->assertSame('8.77', $accountingDTO->getTotalProfitPercentage());
    }

    public function testInvalidAccountingMovementsArgumentThrowsExeption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $invalidAccoutingMovements = [1, 2 , 3];
        $aux = new AccountingDTO(self::$account, self::$oldestYear, self::$year, $invalidAccoutingMovements, self::$totals);
        unset($aux);
    }

    public function testAccountWithNoAccountingMovements(): void
    {
        $account2 = new Account('test3@example.com', 'password', self::$currency, new \DateTimeZone('Europe/Madrid'));
        $accountingDTO = new AccountingDTO($account2, 2022, 2022, [], ['buy' => '0', 'sell' => '0']);
        $this->assertIsArray($accountingDTO->getAccountingMovements());
        $this->assertCount(0, $accountingDTO->getAccountingMovements());
        $this->assertSame($account2, $accountingDTO->getAccount());
        $this->assertSame('0.00', $accountingDTO->getYearPurchasePrice()->getValue());
        $this->assertSame('0.00', $accountingDTO->getYearSoldPrice()->getValue());
        $this->assertSame('0.00', $accountingDTO->getYearForecastProfitPrice()->getValue());
        $this->assertSame('0.00', $accountingDTO->getYearForecastProfitPercentage());   
        $this->assertSame('0.00', $accountingDTO->getTotalPurchasePrice()->getValue());
        $this->assertSame('0.00', $accountingDTO->getTotalSoldPrice()->getValue());
        $this->assertSame('0.00', $accountingDTO->getTotalProfitPrice()->getValue());
        $this->assertSame('0.00', $accountingDTO->getTotalProfitPercentage());
    }
}
