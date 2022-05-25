<?php declare(strict_types=1);

namespace Tests\unit\Application\Command\Stock;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Application\Command\Stock\StockOperateCommand;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\Adquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationRepositoryInterface;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\Transaction\AdquisitionRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine;

/**
 * @covers xVer\MiCartera\Application\Command\Stock\StockOperateCommand
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Adquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Criteria\FiFoCriteria
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Liquidation
 */
class StockOperateCommandTest extends TestCase
{
    private Currency $currency;
    private Account $account;
    /** @var Stock&MockObject */
    private Stock $stock;
    /** @var EntityObjectRepositoryLoader&Stub */
    private EntityObjectRepositoryLoader $repoLoader;

    public function setUp(): void
    {
        /** @var Currency&Stub */
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('sameId')->willReturn(true);
        $this->currency->method('getDecimals')->willReturn(2);
        /** @var Account&Stub */
        $this->account = $this->createStub(Account::class);
        $this->account->method('getCurrency')->willReturn($this->currency);
        $this->account->method('getTimeZone')->willReturn(new DateTimeZone('Europe/Madrid'));
        /** @var Stock&Stub */
        $this->stock = $this->createStub(Stock::class);
        $this->stock->method('getCurrency')->willReturn($this->currency);
        /** @var EntityObjectRepositoryLoader&Stub */
        $this->repoLoader = $this->createStub(EntityObjectRepositoryLoader::class);
    }

    public function testPurchaseCommandSucceeds(): void
    {
        /** @var AdquisitionRepositoryDoctrine&Stub */
        $repoAdquisition = $this->createStub(AdquisitionRepositoryDoctrine::class);
        $repoAdquisition->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        /** @var StockRepositoryDoctrine&Stub */
        $repoStock = $this->createStub(StockRepositoryDoctrine::class);
        $repoStock->method('findByIdOrThrowException')->willReturn($this->stock);
        /** @var AccountRepositoryDoctrine&Stub */
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        $repoAccount->method('findByIdentifierOrThrowException')->willReturn($this->account);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [StockRepositoryInterface::class, $repoStock],
                [AccountRepositoryInterface::class, $repoAccount],
                [AdquisitionRepositoryInterface::class, $repoAdquisition]
            ])
        );
        /** @var StockOperateCommand&MockObject */
        $command = $this->getMockBuilder(StockOperateCommand::class)
        ->enableOriginalConstructor()
        ->setConstructorArgs([$this->repoLoader])
        ->onlyMethods(['newAdquisition'])->getMock();
        $command->method('newAdquisition')->willReturn($this->createStub(Adquisition::class));
        $adquisition = $command->purchase(
            'TEST',
            new DateTime('now', new DateTimeZone('UTC')),
            100,
            '6.5443',
            '5.34',
            'test@example.com'
        );
        $this->assertInstanceOf(Adquisition::class, $adquisition);
    }

    public function testRemovePurchaseCommandSucceeds(): void
    {
        $uuid = Uuid::v4();
        /** @var Adquisition&MockObject */
        $transaction = $this->createMock(Adquisition::class);
        $transaction->expects($this->once())->method('persistRemove');
        /** @var AdquisitionRepositoryDoctrine&Stub */
        $repoTransaction = $this->createStub(AdquisitionRepositoryDoctrine::class);
        $repoTransaction->method('findByIdOrThrowException')->willReturn($transaction);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [AdquisitionRepositoryInterface::class, $repoTransaction]
            ])
        );
        $command = new StockOperateCommand($this->repoLoader);
        $command->removePurchase($uuid->toRfc4122());
    }

    public function testSellCommandSucceeds(): void
    {
        /** @var LiquidationRepositoryDoctrine&Stub */
        $repoLiquidation = $this->createStub(LiquidationRepositoryDoctrine::class);
        $repoLiquidation->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        /** @var StockRepositoryDoctrine&Stub */
        $repoStock = $this->createStub(StockRepositoryDoctrine::class);
        $repoStock->method('findByIdOrThrowException')->willReturn($this->stock);
        /** @var AccountRepositoryDoctrine&Stub */
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        $repoAccount->method('findByIdentifierOrThrowException')->willReturn($this->account);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [StockRepositoryInterface::class, $repoStock],
                [AccountRepositoryInterface::class, $repoAccount],
                [LiquidationRepositoryInterface::class, $repoLiquidation]
            ])
        );
        /** @var StockOperateCommand&MockObject */
        $command = $this->getMockBuilder(StockOperateCommand::class)
        ->enableOriginalConstructor()
        ->setConstructorArgs([$this->repoLoader])
        ->onlyMethods(['newLiquidation'])->getMock();
        $command->method('newLiquidation')->willReturn($this->createStub(Liquidation::class));
        $liquidation = $command->sell(
            'TEST',
            new DateTime('now', new DateTimeZone('UTC')),
            100,
            '6.5443',
            '5.34',
            'test@example.com'
        );
        $this->assertInstanceOf(Liquidation::class, $liquidation);
    }

    public function testRemoveSellCommandSucceeds(): void
    {
        $uuid = Uuid::v4();
        /** @var Liquidation&MockObject */
        $transaction = $this->createMock(Liquidation::class);
        $transaction->expects($this->once())->method('persistRemove');
        /** @var LiquidationRepositoryDoctrine&Stub */
        $repoTransaction = $this->createStub(LiquidationRepositoryDoctrine::class);
        $repoTransaction->method('findByIdOrThrowException')->willReturn($transaction);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [LiquidationRepositoryInterface::class, $repoTransaction]
            ])
        );
        $command = new StockOperateCommand($this->repoLoader);
        $command->removeSell($uuid->toRfc4122());
    }

    /**
     * @dataProvider validImportData
     */
    public function testImportCommandSucceeds($line, $repoTransInterface, $repoTransClass, $commandMethod): void
    {
        /** @var Stub */
        $repoTransaction = $this->createStub($repoTransClass);
        $repoTransaction->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        /** @var StockRepositoryDoctrine&Stub */
        $repoStock = $this->createStub(StockRepositoryDoctrine::class);
        $repoStock->method('findByIdOrThrowException')->willReturn($this->stock);
        /** @var AccountRepositoryDoctrine&Stub */
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        $repoAccount->method('findByIdentifierOrThrowException')->willReturn($this->account);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [StockRepositoryInterface::class, $repoStock],
                [AccountRepositoryInterface::class, $repoAccount],
                [$repoTransInterface, $repoTransaction]
            ])
        );
        /** @var StockOperateCommand&MockObject */
        $command = $this->getMockBuilder(StockOperateCommand::class)
        ->enableOriginalConstructor()
        ->setConstructorArgs([$this->repoLoader])
        ->onlyMethods([$commandMethod])->getMock();
        $command->expects($this->once())->method($commandMethod);
        $command->import(
            1,
            $line,
            'test@example.com'
        );
    }

    public static function validImportData(): array
    {
        return [
            [['2023-03-22 10:12:44','adquisition','TEST','56.7665',100,'3.67'], AdquisitionRepositoryInterface::class, AdquisitionRepositoryDoctrine::class, 'newAdquisition'],
            [['2023-03-24 11:22:12','liquidation','TEST','61.2143',450,'6.99'], LiquidationRepositoryInterface::class, LiquidationRepositoryDoctrine::class, 'newLiquidation']
        ];
    }

    /**
     * @dataProvider invalidImportData
     */
    public function testCreateBatchFromCSVCommandWithInvalidDataThrowException($line): void
    {
        /** @var AdquisitionRepositoryDoctrine&Stub */
        $repoTransaction = $this->createStub(AdquisitionRepositoryDoctrine::class);
        $repoTransaction->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        $repoStock = $this->createStub(StockRepositoryDoctrine::class);
        /** @var AccountRepositoryDoctrine&Stub */
        $repoAccount = $this->createStub(AccountRepositoryDoctrine::class);
        $repoAccount->method('findByIdentifierOrThrowException')->willReturn($this->account);
        $this->repoLoader->method('load')->will(
            $this->returnValueMap([
                [StockRepositoryInterface::class, $repoStock],
                [AccountRepositoryInterface::class, $repoAccount],
                [AdquisitionRepositoryInterface::class, $repoTransaction]
            ])
        );
        $command = new StockOperateCommand($this->repoLoader);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('importCsvDomainError');
        $command->import(1, $line,'test@example.com');
    }

    public static function invalidImportData(): array
    {
        $date = new DateTime('yesterday', new DateTimeZone('UTC'));
        return [
            [[$date->format('Y-m-d H:i:s'),'invalid','TEST','5.66',100,'3,67']], //Invalid transaction type
            [[$date->format('Y-m-d'),'adquisition','TEST','5.66',100,'3.67']], //Invalid date
            [[$date->format('Y-m-d H:i:s'),'adquisition','TEST','5,66',100,'3.67']], //Invalid price format
            [[$date->format('Y-m-d H:i:s'),'adquisition','TEST','5.66',100,'3,67']], //Invalid expenses format
        ];
    }
}
