<?php declare(strict_types=1);

namespace Tests\integration\Infrastructure\Stock\Transaction\Criteria;

use DateInterval;
use DateTime;
use DateTimeZone;
use Tests\integration\IntegrationTestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Accounting\MovementRepositoryInterface;
use xVer\MiCartera\Domain\Accounting\MovementsCollection;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\Adquisition;
use xVer\MiCartera\Domain\Stock\Transaction\Criteria\FiFoCriteria;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationRepositoryInterface;

/**
 * @covers xVer\MiCartera\Domain\Stock\Transaction\Criteria\FifoCriteria
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Accounting\Movement
 * @uses xVer\MiCartera\Domain\Accounting\MovementsCollection
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Adquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Liquidation
 * @uses xVer\MiCartera\Domain\Stock\Transaction\LiquidationsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Accounting\MovementRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\AdquisitionRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine
 */
class FifoCriteriaTest extends IntegrationTestCase
{
    private Account $account;
    private Stock $stock;
    private Stock $stock2;
    private MoneyVO $expenses;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->account = $this->repoLoader->load(AccountRepositoryInterface::class)->findByIdentifierOrThrowException('test@example.com');
        $this->stock = $this->repoLoader->load(StockRepositoryInterface::class)->findByIdOrThrowException('CABK');
        $this->stock2 = $this->repoLoader->load(StockRepositoryInterface::class)->findByIdOrThrowException('SAN');
        $this->expenses = new MoneyVO('4.56', $this->account->getCurrency());
    }

    public function testNoAdquistionBeforeDateThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transNotPassFifoSpec');
        new Liquidation(
            $this->repoLoader,
            $this->stock,
            new DateTime('last month', new DateTimeZone('UTC')),
            100,
            $this->expenses,
            $this->account
        );
    }

    public function testNotEnoughAdquistionAmountOutstandingThrowsException(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transNotPassFifoSpec');
        new Liquidation(
            $this->repoLoader,
            $this->stock,
            new DateTime('30 minutes ago', new DateTimeZone('UTC')),
            201,
            $this->expenses,
            $this->account
        );
    }

    public function testNotEnoughAdquistionAmountOutstandingAfterRearrangementThrowsException(): void
    {
        parent::$loadFixtures = true;
        new Liquidation(
            $this->repoLoader,
            $this->stock,
            new DateTime('30 minutes ago', new DateTimeZone('UTC')),
            1,
            $this->expenses,
            $this->account
        );
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transNotPassFifoSpec');
        new Liquidation(
            $this->repoLoader,
            $this->stock,
            new DateTime('40 minutes ago', new DateTimeZone('UTC')),
            200,
            $this->expenses,
            $this->account
        );
    }
    
    public function testFifo(): void
    {
        parent::$loadFixtures = true;
        /** @var Adquisition[] */
        $adquisitions = [];
        /** @var Liquidation[] */
        $liquidations = [];
        $repoMovement = $this->repoLoader->load(MovementRepositoryInterface::class);
        $date = new DateTime('now', new DateTimeZone('UTC'));
        // Test create adquisition generating no accounting movements
        $adquisitions[0] = new Adquisition(
            $this->repoLoader,
            $this->stock2, (clone $date)->sub(new DateInterval('PT60M')), 1000, $this->expenses, $this->account
        );
        $expectedMovements = [];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($repoMovement));
        $expectedAmountOutstanding = [
            0 => 1000
        ];
        $this->checkAdquisitionsAmountOutstanding($adquisitions, $expectedAmountOutstanding);
    
        // Test create liquidation requiring no accounting movements rearrangements
        $liquidations[0] = new Liquidation(
            $this->repoLoader,
            $this->stock2, (clone $date)->sub(new DateInterval('PT30M')), 500, $this->expenses, $this->account
        );
        $expectedMovements = [
            0 => ["adquisition" => $adquisitions[0], "liquidation" => $liquidations[0], "amount" => 500]
        ];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($repoMovement));
        $expectedAmountOutstanding = [
            0 => 500
        ];
        $this->checkAdquisitionsAmountOutstanding($adquisitions, $expectedAmountOutstanding);
        
        // Test create adquisition requiring accounting movement rearrangement
        $adquisitions[1] = new Adquisition(
            $this->repoLoader,
            $this->stock2, (clone $date)->sub(new DateInterval('PT90M')), 200, $this->expenses, $this->account
        );
        $expectedMovements = [
            0 => ["adquisition" => $adquisitions[1], "liquidation" => $liquidations[0], "amount" => 200],
            1 => ["adquisition" => $adquisitions[0], "liquidation" => $liquidations[0], "amount" => 300]
        ];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($repoMovement));
        $expectedAmountOutstanding = [
            0 => 700,
            1 => 0
        ];
        $this->checkAdquisitionsAmountOutstanding($adquisitions, $expectedAmountOutstanding);
     
        // Test create liquidation requiring accounting movement rearrangement
        $liquidations[1] = new Liquidation(
            $this->repoLoader,
            $this->stock2, (clone $date)->sub(new DateInterval('PT86M')), 100, $this->expenses, $this->account
        );
        $expectedMovements = [
            0 => ["adquisition" => $adquisitions[1], "liquidation" => $liquidations[1], "amount" => 100],
            1 => ["adquisition" => $adquisitions[1], "liquidation" => $liquidations[0], "amount" => 100],
            2 => ["adquisition" => $adquisitions[0], "liquidation" => $liquidations[0], "amount" => 400]
        ];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($repoMovement));
        $expectedAmountOutstanding = [
            0 => 600,
            1 => 0
        ];
        $this->checkAdquisitionsAmountOutstanding($adquisitions, $expectedAmountOutstanding);
   
        // Test create other liquidation requiring accounting movement rearrangement
        $liquidations[2] = new Liquidation(
            $this->repoLoader,
            $this->stock2, (clone $date)->sub(new DateInterval('PT31M')), 500, $this->expenses, $this->account
        );
        $expectedMovements = [
            0 => ["adquisition" => $adquisitions[1], "liquidation" => $liquidations[1], "amount" => 100],
            1 => ["adquisition" => $adquisitions[1], "liquidation" => $liquidations[2], "amount" => 100],
            2 => ["adquisition" => $adquisitions[0], "liquidation" => $liquidations[2], "amount" => 400],
            3 => ["adquisition" => $adquisitions[0], "liquidation" => $liquidations[0], "amount" => 500]
        ];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($repoMovement));
        $expectedAmountOutstanding = [
            0 => 100,
            1 => 0
        ];
        $this->checkAdquisitionsAmountOutstanding($adquisitions, $expectedAmountOutstanding);
    
        // Test remove liquidation requiring accounting movements rearrangement
        $liquidations[1]->persistRemove($this->repoLoader, new FiFoCriteria($this->repoLoader));
        $expectedMovements = [
            0 => ["adquisition" => $adquisitions[1], "liquidation" => $liquidations[2], "amount" => 200],
            1 => ["adquisition" => $adquisitions[0], "liquidation" => $liquidations[2], "amount" => 300],
            2 => ["adquisition" => $adquisitions[0], "liquidation" => $liquidations[0], "amount" => 500]
        ];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($repoMovement));
        $expectedAmountOutstanding = [
            0 => 200,
            1 => 0
        ];
        $this->checkAdquisitionsAmountOutstanding($adquisitions, $expectedAmountOutstanding);
    
        // Test remove other liquidation not requiring accounting movements rearrangement
        $liquidations[0]->persistRemove($this->repoLoader, new FiFoCriteria($this->repoLoader));
        $expectedMovements = [
            0 => ["adquisition" => $adquisitions[1], "liquidation" => $liquidations[2], "amount" => 200],
            1 => ["adquisition" => $adquisitions[0], "liquidation" => $liquidations[2], "amount" => 300]
        ];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($repoMovement));
        $expectedAmountOutstanding = [
            0 => 700,
            1 => 0
        ];
        $this->checkAdquisitionsAmountOutstanding($adquisitions, $expectedAmountOutstanding);
    
        // Test add adquisition not requiring rearrangement
        $adquisitions[2] = new Adquisition(
            $this->repoLoader,
            $this->stock2, (clone $date)->sub(new DateInterval('PT20M')), 200, $this->expenses, $this->account
        );
        $this->checkMovements($expectedMovements, $this->retrieveMovements($repoMovement));
        $expectedAmountOutstanding = [
            0 => 700,
            1 => 0,
            2 => 200
        ];
        $this->checkAdquisitionsAmountOutstanding($adquisitions, $expectedAmountOutstanding);
    
        // Test adding liquidation with insufficient amount outstanding throws exception
        $exceptionsThrown = 0;
        $exceptionsMessagesCorrect = 0;
        try {
            new Liquidation(
                $this->repoLoader,
                $this->stock2, (clone $adquisitions[1]->getDateTimeUtc())->sub(new DateInterval('PT30S')), 1000, $this->expenses, $this->account
            );
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
            $this->resetEntityManager();
        }
        try {
            new Liquidation(
                $this->repoLoader,
                $this->stock2, (clone  $liquidations[2]->getDateTimeUtc())->sub(new DateInterval('PT30S')), 1200, $this->expenses, $this->account
            );
        } catch (DomainException $th) {
            $exceptionsThrown++;
            if ($th->getMessage() === 'transNotPassFifoSpec') {
                $exceptionsMessagesCorrect++;
            }
            $this->resetEntityManager();
        }
        $this->assertSame(2, $exceptionsThrown);
        $this->assertSame(2, $exceptionsMessagesCorrect);

        // Test remove liquidation not requiring rearrangement
        $this->repoLoader->load(LiquidationRepositoryInterface::class)
        ->findById($liquidations[2]->getId())
        ->persistRemove($this->repoLoader, new FiFoCriteria($this->repoLoader));
        $expectedMovements = [];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($repoMovement));

        // Test adding liquidation requiring accounting movements rearrangement
        // causes existing liquidation not find adquistions with enough
        // amount outstanding
        new Liquidation(
            $this->repoLoader,
            $this->stock2, (clone $date)->sub(new DateInterval('PT50M')), 1000, $this->expenses, $this->account
        );
        new Liquidation(
            $this->repoLoader,
            $this->stock2, (clone $date)->sub(new DateInterval('PT25M')), 200, $this->expenses, $this->account
        );
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('transNotPassFifoSpec');
        new Liquidation(
            $this->repoLoader,
            $this->stock2, (clone $date)->sub(new DateInterval('PT55M')), 200, $this->expenses, $this->account
        );
    }

    private function checkMovements(
        array $expectedMovements,
        MovementsCollection $persistedMovements
    ): void {
        $this->assertSame(count($expectedMovements), $persistedMovements->count());
        foreach ($persistedMovements->toArray() as $key => $persistedAccountingMovement) {
            $this->assertTrue($persistedAccountingMovement->getAdquisition()->sameId($expectedMovements[$key]['adquisition']));
            $this->assertTrue($persistedAccountingMovement->getLiquidation()->sameId($expectedMovements[$key]['liquidation']));
            $this->assertSame($persistedAccountingMovement->getAmount(), $expectedMovements[$key]['amount']);
        }
    }

    private function retrieveMovements(MovementRepositoryInterface $repoAccountingMovement): MovementsCollection
    {
        return $repoAccountingMovement->findByAccountStockAdquisitionDateAfter($this->account, $this->stock2, new DateTime('30 days ago', new DateTimeZone('UTC')));
    }

    private function checkAdquisitionsAmountOutstanding(array $adquisitions,array $expectedAdquisitionsAmountOutstanding): void
    {
        foreach ($adquisitions as $key => $adquisition) {
            $this->assertSame($adquisition->getAmountOutstanding(), $expectedAdquisitionsAmountOutstanding[$key]);
        }
    }
}
