<?php declare(strict_types=1);

namespace Tests\integration\Infrastructure\Currency;

use Tests\integration\IntegrationTestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Currency\CurrenciesCollection;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\Currency\CurrenciesCollection
 * @uses xVer\MiCartera\Domain\Exchange\Exchange
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Acquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Criteria\FifoCriteria
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Exchange\ExchangeRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\AcquisitionRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine
 */
class CurrencyRepositoryDoctrineTest extends IntegrationTestCase
{
    private CurrencyRepositoryDoctrine $repoCurrency;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->repoCurrency = new CurrencyRepositoryDoctrine(self::$registry);
    }

    public function testCurrencyIsPersisted(): void
    {
        parent::$loadFixtures = true;
        $currency = new Currency($this->repoLoader, 'GBP', '£', 2);
        $this->repoCurrency->persist($currency);
        parent::detachEntity($currency);
        $this->assertInstanceOf(Currency::class, $this->repoCurrency->findById($currency->getIso3()));
    }

    public function testCurrencyIsFoundById(): void
    {
        $currency = $this->repoCurrency->findById('EUR');
        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertSame('EUR', $currency->getIso3());
    }

    public function testCurrencyIsFoundByIdOrThrowsException(): void
    {
        $currency = $this->repoCurrency->findByIdOrThrowException('EUR');
        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertSame('EUR', $currency->getIso3());
    }

    public function testCurrencyIsFoundByIdOrThrowsExceptionWhenNotFoundWillThrowException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('expectedPersistedObjectNotFound');
        $this->repoCurrency->findByIdOrThrowException('XXX');
    }

    public function testAll(): void
    {
        $currenciesCollection = $this->repoCurrency->all();
        $this->assertInstanceOf(CurrenciesCollection::class, $currenciesCollection);
    }
}
