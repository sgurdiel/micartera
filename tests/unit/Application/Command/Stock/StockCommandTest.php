<?php declare(strict_types=1);

namespace Tests\unit\Application\Command\Stock;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Application\Command\Stock\StockCommand;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Exchange\ExchangeRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationRepositoryInterface;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Exchange\ExchangeRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\Transaction\AcquisitionRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Application\Command\Stock\StockCommand
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Domain\Stock\StocksCollection
 */
class StockCommandTest extends TestCase
{
    private Currency&Stub $currency;
    private Stock&MockObject $stock;
    private StockRepositoryDoctrine&MockObject $repoStock;
    private EntityObjectRepositoryLoaderInterface&MockObject $repoLoader;

    public function setUp(): void
    {
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('sameId')->willReturn(true);
        $this->stock = $this->createMock(Stock::class);
        $this->stock->method('getCurrency')->willReturn($this->currency);
        $this->repoStock = $this->createMock(StockRepositoryDoctrine::class);
        $this->repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
    }

    public function testCreateCommandSucceeds(): void
    {
        /** @var Account&Stub */
        $account = $this->createStub(Account::class);
        $account->method('getCurrency')->willReturn($this->currency);
        /** @var AccountRepositoryDoctrine&Stub */
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        $repoAccount->method('findByIdentifierOrThrowException')->willReturn($account);
        $repoStock = $this->createStub(StockRepositoryDoctrine::class);
        $repoExchange = $this->createStub(ExchangeRepositoryDoctrine::class);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [StockRepositoryInterface::class, $repoStock],
                [AccountRepositoryInterface::class, $repoAccount],
                [ExchangeRepositoryInterface::class, $repoExchange]
            ])
        );
        $command = new StockCommand($this->repoLoader);
        $stock = $command->create('TEST', 'Test', '5.44', 'test@example.com', 'BME');
        $this->assertInstanceOf(Stock::class, $stock);
    }

    public function testUpdateCommandSucceeds(): void
    {
        $this->repoStock->expects($this->once())->method('findByIdOrThrowException')->willReturn($this->stock);
        $this->stock->expects($this->once())->method('persistUpdate');
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [StockRepositoryInterface::class, $this->repoStock]
            ])
        );
        $command = new StockCommand($this->repoLoader);
        $command->update('TEST', 'Test', '5.44');
    }

    public function testRemoveCommandSucceeds(): void
    {
        $this->repoStock->expects($this->once())->method('findByIdOrThrowException')->willReturn($this->stock);
        $this->stock->expects($this->once())->method('persistRemove');
        $repoAcquisition = $this->createStub(AcquisitionRepositoryDoctrine::class);
        $repoLiquidation = $this->createStub(LiquidationRepositoryDoctrine::class);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [StockRepositoryInterface::class, $this->repoStock],
                [AcquisitionRepositoryInterface::class, $repoAcquisition],
                [LiquidationRepositoryInterface::class, $repoLiquidation]
            ])
        );
        $command = new StockCommand($this->repoLoader);
        $command->delete('TEST');
    }
}
