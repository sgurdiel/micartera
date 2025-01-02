<?php declare(strict_types=1);

namespace Tests\integration\Infrastructure\Exchange;

use Tests\integration\IntegrationTestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Exchange\ExchangesCollection;
use xVer\MiCartera\Domain\Exchange\Exchange;
use xVer\MiCartera\Infrastructure\Exchange\ExchangeRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Infrastructure\Exchange\ExchangeRepositoryDoctrine
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\Exchange\Exchange
 * @uses xVer\MiCartera\Domain\Exchange\ExchangesCollection
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Acquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Criteria\FiFoCriteria
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\AcquisitionRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine
 */
class ExchangeRepositoryDoctrineTest extends IntegrationTestCase
{
    private ExchangeRepositoryDoctrine $repoExchange;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->repoExchange = new ExchangeRepositoryDoctrine(self::$registry);
    }

    public function testExchangeIsPersisted(): void
    {
        parent::$loadFixtures = true;
        $exchange = new Exchange($this->repoLoader, 'CODE', 'NAME');
        $this->repoExchange->persist($exchange);
        parent::detachEntity($exchange);
        $this->assertInstanceOf(Exchange::class, $this->repoExchange->findById($exchange->getCode()));
    }

    public function testExchangeIsFoundById(): void
    {
        $exchange = $this->repoExchange->findById('MCE');
        $this->assertInstanceOf(Exchange::class, $exchange);
        $this->assertSame('MCE', $exchange->getCode());
    }

    public function testExchangeIsFoundByIdOrThrowsException(): void
    {
        $exchange = $this->repoExchange->findByIdOrThrowException('MCE');
        $this->assertInstanceOf(Exchange::class, $exchange);
        $this->assertSame('MCE', $exchange->getCode());
    }

    public function testExchangeIsFoundByIdOrThrowsExceptionWhenNotFoundWillThrowException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('expectedPersistedObjectNotFound');
        $this->repoExchange->findByIdOrThrowException('XXX');
    }

    public function testAll(): void
    {
        $exchangesCollection = $this->repoExchange->all();
        $this->assertInstanceOf(ExchangesCollection::class, $exchangesCollection);
    }
}
