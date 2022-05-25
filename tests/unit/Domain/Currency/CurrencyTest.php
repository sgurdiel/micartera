<?php declare(strict_types=1);

namespace Tests\unit\Domain\Currency;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Currency\CurrencyRepositoryInterface;

/**
 * @covers xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\Stock\Stock
 */
class CurrencyTest extends TestCase
{
    /** @var EntityObjectRepositoryLoaderInterface&MockObject */
    private EntityObjectRepositoryLoaderInterface $repoLoader;
    /** @var CurrencyRepositoryInterface&MockObject */
    private CurrencyRepositoryInterface $repoCurrency;

    public function setUp(): void
    {
        $this->repoCurrency = $this->createMock(CurrencyRepositoryInterface::class);
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $this->repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $this->repoLoader->method('load')->willReturn($this->repoCurrency);
    }

    public function testDuplicateCodeThrowsException(): void
    {
        $this->repoCurrency->method('findById')->willReturn($this->createStub(Currency::class));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('currencyExists');
        new Currency($this->repoLoader, 'EUR', '€', 2);
    }

    public function testCurrencyValueObjectIsCreated(): void
    {
        $iso3 = 'EUR';
        $symbol = "€";
        $decimals = 2;
        $curreny = new Currency($this->repoLoader, $iso3, $symbol, $decimals);
        $this->assertSame($iso3, $curreny->getISO3());
        $this->assertSame($symbol, $curreny->getSymbol());
        $this->assertSame($decimals, $curreny->getDecimals());
        $this->assertTrue($curreny->sameId($curreny));
    }

    /** @dataProvider invalidCodes */
    public function testInvalidCodeThrowExceptions($testCode): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('invalidIso3');
        new Currency($this->repoLoader, $testCode, '€', 2);
    }

    public static function invalidCodes(): array
    {
        return [
            [''],
            ['A'],
            ['AA'],
            ['AAAA']
        ];
    }

    /** @dataProvider invalidSymbols */
    public function testInvalidSymbolThrowExceptions($testSymbol): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('invalidSymbol');
        new Currency($this->repoLoader, 'ABC', $testSymbol, 2);
    }

    public static function invalidSymbols(): array
    {
        return [
            [''],
            ['12345678901'],
        ];
    }

    /** @dataProvider invalidPrecisions */
    public function testInvalidPrecisionThrowExceptions($testPrecision): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('numberBetween');
        new Currency($this->repoLoader, 'ABC', '€', $testPrecision);
    }

    public static function invalidPrecisions(): array
    {
        return [
            [5],
            [0],
            [-1],
        ];
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $currency = new Currency($this->repoLoader, 'EUR', '€', 2);
        $entity = new class implements EntityObjectInterface { public function sameId(EntityObjectInterface $otherEntity): bool { return true; }};
        $currency->sameId($entity);
    }

    public function testExceptionIsThrownOnCommitFail(): void
    {
        $this->repoCurrency->expects($this->once())->method('persist')->willThrowException(new Exception());        
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        new Currency($this->repoLoader, 'EUR', '€', 2);
    }
}
