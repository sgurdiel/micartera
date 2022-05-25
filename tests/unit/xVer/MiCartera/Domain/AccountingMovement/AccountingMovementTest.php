<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Domain\AccountingMovement;

use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;

/**
 * @covers xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 */
class AccountingMovementTest extends TestCase
{
    private static Transaction $buyTransaction;
    private static Transaction $sellTransaction;
    private static Transaction $sellTransactionDiffStock;
    private static Transaction $sellTransactionWrongDate;

    public static function setUpBeforeClass(): void
    {
        $currency = new Currency('EUR', '€', 2);
        $price = new StockPriceVO('4.56', $currency);
        $stock = new Stock('ABCD', 'ABCD Name', $price);
        $dateTimeUtc = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        $expenses = new MoneyVO('23.34', $currency);
        $account = new Account('test@example.com', 'password', $currency, new \DateTimeZone("Europe/Madrid"), ['ROLE_USER']);
        self::$buyTransaction = new Transaction(Transaction::TYPE_BUY, $stock, $dateTimeUtc, 100, $expenses, $account);
        $dateTimeUtc2 = clone $dateTimeUtc;
        $dateTimeUtc2->add(new \DateInterval("PT10S"));
        self::$sellTransaction = new Transaction(Transaction::TYPE_SELL, $stock, $dateTimeUtc2, 10, $expenses, $account);
        $stock2 = new Stock('EFG', 'EFG Name', $price);
        self::$sellTransactionDiffStock = new Transaction(Transaction::TYPE_SELL, $stock2, $dateTimeUtc2, 10, $expenses, $account);
        self::$sellTransactionWrongDate = new Transaction(Transaction::TYPE_SELL, $stock, $dateTimeUtc->sub(new \DateInterval("PT1S")), 10, $expenses, $account);
    }

    public function testIsCreated(): void
    {
        $accountingMovement = new AccountingMovement(self::$buyTransaction, self::$sellTransaction, 10);
        $this->assertSame(self::$buyTransaction, $accountingMovement->getBuyTransaction());
        $this->assertSame(self::$sellTransaction, $accountingMovement->getSellTransaction());
        $this->assertSame(10, $accountingMovement->getAmount());
        $this->assertTrue($accountingMovement->sameId($accountingMovement));
    }

    public function testAmountIsUpdate(): void
    {
        $accountingMovement = new AccountingMovement(self::$buyTransaction, self::$sellTransaction, 10);
        $accountingMovement->setAmount(20);
        $this->assertSame(20, $accountingMovement->getAmount());
    }

    public function testAmountFormat(): void
    {
        $amounts = [-1, 100000];
        $exceptionsExpected = count($amounts);
        $exceptionsThrown = 0;
        $exceptionsMessagesCorrect = 0;
        foreach ($amounts as $amount) {
            try {
                $aux = new AccountingMovement(self::$buyTransaction, self::$sellTransaction, $amount);
                unset($aux);
            } catch (DomainException $th) {
                $exceptionsThrown++;
                if ($th->getMessage() === 'numberBetween') {
                    $exceptionsMessagesCorrect++;
                }
            }
        }
        $this->assertSame($exceptionsExpected, $exceptionsThrown);
        $this->assertSame($exceptionsExpected, $exceptionsMessagesCorrect);
    }

    public function testBuyTransactionWithWrongTypeThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transactionAssertType');
        $aux = new AccountingMovement(self::$sellTransaction, self::$sellTransaction, 10);
        unset($aux);
    }

    public function testSellTransactionWithWrongTypeThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transactionAssertType');
        $aux = new AccountingMovement(self::$buyTransaction, self::$buyTransaction, 10);
        unset($aux);
    }

    public function testSellAndBuyTransactionWithDifferentStockThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transactionAssertStock');
        $aux = new AccountingMovement(self::$buyTransaction, self::$sellTransactionDiffStock, 10);
        unset($aux);
    }

    public function testSellTransactionNotPastTimeThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('accountingMovementAssertDateTime');
        $aux = new AccountingMovement(self::$buyTransaction, self::$sellTransactionWrongDate, 10);
        unset($aux);
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $accountingMovement = new AccountingMovement(self::$buyTransaction, self::$sellTransaction, 10);
        $stock = new Stock('ABCD', 'ABCD Name', new StockPriceVO('44.3211', $accountingMovement->getBuyTransaction()->getCurrency()));
        $accountingMovement->sameId($stock);
    }
}
