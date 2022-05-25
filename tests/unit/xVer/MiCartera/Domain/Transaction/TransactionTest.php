<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Domain\Transaction;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\AccountingMovement\AccountingMovement;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;

/**
 * @covers xVer\MiCartera\Domain\Transaction\Transaction
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 */
class TransactionTest extends TestCase
{
    private static Currency $currency;
    private static StockPriceVO $price;
    private static Stock $stock;
    private static \DateTime $dateTimeUtc;
    private static int $amount;
    private static MoneyVO $prices;
    private static Account $account;

    public static function setUpBeforeClass(): void
    {
        self::$currency = new Currency('EUR', '€', 2);
        self::$price = new StockPriceVO('4.5600', self::$currency);
        self::$stock = new Stock('ABCD', 'ABCD Name', self::$price);
        self::$dateTimeUtc = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        self::$amount = 100;
        self::$prices = new MoneyVO('23.34', self::$currency);
        self::$account = new Account('test@example.com', 'password', self::$currency, new \DateTimeZone("Europe/Madrid"), ['ROLE_USER']);
    }

    public function testIsCreated(): void
    {
        $transaction = new Transaction(
            Transaction::TYPE_BUY, 
            self::$stock, 
            self::$dateTimeUtc, 
            self::$amount,
            self::$prices, 
            self::$account);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertSame(Transaction::TYPE_BUY, $transaction->getType());
        $this->assertSame(self::$stock, $transaction->getStock());
        $this->assertEquals(self::$dateTimeUtc->format('Y-m-d H:i:s'), $transaction->getDateTimeUtc()->format('Y-m-d H:i:s'));
        $this->assertSame(self::$amount, $transaction->getAmount());
        $this->assertEquals(self::$price, $transaction->getPrice());
        $this->assertEquals(self::$prices, $transaction->getExpenses());
        $this->assertSame(self::$account, $transaction->getAccount());
        $this->assertInstanceOf(Uuid::class, $transaction->getId());
        $this->assertSame(self::$currency, $transaction->getCurrency());
        $this->assertTrue($transaction->sameId($transaction));
        $this->assertSame(self::$amount, $transaction->getAmountOutstanding());
    }

    public function testTypes(): void
    {
        $transTypes = [Transaction::TYPE_BUY, Transaction::TYPE_SELL];
        foreach ($transTypes as $transType) {
            $transaction = new Transaction(
                $transType, 
                self::$stock, 
                self::$dateTimeUtc, 
                self::$amount, 
                self::$prices, 
                self::$account);
            $this->assertSame($transType, $transaction->getType());
        }
    }

    public function testInvalidTypeThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('invalidTransactionType');
        $transaction = new Transaction(
            -1, 
            self::$stock, 
            self::$dateTimeUtc, 
            self::$amount, 
            self::$prices, 
            self::$account);
        unset($transaction);
    }

    public function testDateInFutureThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('futureDateNotAllowed');
        $aux =new Transaction(
            Transaction::TYPE_SELL, 
            self::$stock, 
            new \DateTime('tomorrow', new \DateTimeZone('UTC')), 
            self::$amount,
            self::$prices, 
            self::$account
        );
        unset($aux);
    }

    public function testInvalidAmountFormatThrowsException(): void
    {
        $transAmounts = [-1, 100000];
        $exceptionsExpected = count($transAmounts);
        $exceptionsThrown = 0;
        $exceptionsMessagesCorrect = 0;
        foreach ($transAmounts as $transAmount) {
            try {
                $aux = new Transaction(
                    Transaction::TYPE_BUY, 
                    self::$stock, 
                    self::$dateTimeUtc, 
                    $transAmount,
                    self::$prices, 
                    self::$account);
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

    public function testUpdatePriceWithInvalidCurrencyThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('invalidCurrency');
        $buyTransaction = new Transaction(Transaction::TYPE_BUY, self::$stock, self::$dateTimeUtc, self::$amount, self::$prices, self::$account);
        $badCurrency = new Currency('USD', '$', 2);
        $badPrice = new StockPriceVO('3.7700', $badCurrency);
        $buyTransaction->setPrice($badPrice);
    }

    public function testUpdateExpensesWithInvalidCurrencyThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('otherCurrencyExpected');
        $transaction = new Transaction(       
            Transaction::TYPE_BUY, 
            self::$stock, 
            self::$dateTimeUtc, 
            self::$amount,
            self::$prices, 
            self::$account);
        $badCurrency = new Currency('USD', '$', 2);
        $badExpenses = new MoneyVO('5.4', $badCurrency);
        $transaction->setExpenses($badExpenses);
    }
    
    public function testInvalidExpensesValueFormatThrowsException(): void
    {
        $transPrices = ['100000','-1.5'];
        $exceptionsExpected = count($transPrices);
        $exceptionsThrown = 0;
        $exceptionsMessagesCorrect = 0;
        foreach ($transPrices as $transPrice) {
            try {
                $aux = new Transaction(Transaction::TYPE_BUY, self::$stock, self::$dateTimeUtc, self::$amount, new MoneyVO($transPrice, self::$currency), self::$account);
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

    public function testSameIdWithIncorrectEntityArgumentThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $buyTransaction = new Transaction(Transaction::TYPE_BUY, self::$stock, new \DateTime('now', new \DateTimeZone('UTC')), self::$amount, self::$prices, self::$account);
        $account = new Account("test@example.com", "password", self::$currency, new \DateTimeZone('UTC'), ['ROLE_USER']);
        $buyTransaction->sameId($account);
    }

    public function testSetAmountOutstanding(): void
    {
        $buyTransaction = new Transaction(
            Transaction::TYPE_BUY, 
            self::$stock, 
            self::$dateTimeUtc, 
            self::$amount,
            self::$prices, 
            self::$account
        );
        $dateTime2 = clone self::$dateTimeUtc;
        $dateTime2->add(new \DateInterval('PT30S'));
        $sellTransaction = new Transaction(
            Transaction::TYPE_SELL, 
            self::$stock, 
            $dateTime2, 
            self::$amount,
            self::$prices, 
            self::$account
        );
        $accountingMovement = new AccountingMovement(
            $buyTransaction,
            $sellTransaction,
            self::$amount
        );
        $buyTransaction->setAmountOutstanding($accountingMovement, false);
        $this->assertSame(0, $buyTransaction->getAmountOutstanding());
        $buyTransaction->setAmountOutstanding($accountingMovement, true);
        $this->assertSame(self::$amount, $buyTransaction->getAmountOutstanding());
    }

    public function testSetAmountOutstandingOnSellTransactionThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transactionAssertType');
        $buyTransaction = new Transaction(
            Transaction::TYPE_BUY, 
            self::$stock, 
            self::$dateTimeUtc, 
            self::$amount,
            self::$prices, 
            self::$account
        );
        $dateTime2 = clone self::$dateTimeUtc;
        $dateTime2->add(new \DateInterval('PT30S'));
        $sellTransaction = new Transaction(
            Transaction::TYPE_SELL, 
            self::$stock, 
            $dateTime2, 
            self::$amount,
            self::$prices, 
            self::$account
        );
        $accountingMovement = new AccountingMovement(
            $buyTransaction,
            $sellTransaction,
            self::$amount
        );
        $sellTransaction->setAmountOutstanding($accountingMovement, false);
    }

    public function testSetAmountOutstandingWithDifferentBuyTransactionThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $buyTransaction = new Transaction(
            Transaction::TYPE_BUY, 
            self::$stock, 
            self::$dateTimeUtc, 
            self::$amount,
            self::$prices, 
            self::$account
        );
        $dateTime2 = clone self::$dateTimeUtc;
        $dateTime2->add(new \DateInterval('PT10S'));
        $buyTransaction2 = new Transaction(
            Transaction::TYPE_BUY, 
            self::$stock, 
            $dateTime2, 
            self::$amount,
            self::$prices, 
            self::$account
        );
        $dateTime3 = clone self::$dateTimeUtc;
        $dateTime3->add(new \DateInterval('PT30S'));
        $sellTransaction = new Transaction(
            Transaction::TYPE_SELL, 
            self::$stock, 
            $dateTime3, 
            self::$amount,
            self::$prices, 
            self::$account
        );
        $accountingMovement = new AccountingMovement(
            $buyTransaction2,
            $sellTransaction,
            self::$amount
        );
        $buyTransaction->setAmountOutstanding($accountingMovement, false);
    }
}
