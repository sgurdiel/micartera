<?php declare(strict_types=1);

namespace Tests\unit\Domain\Exchange;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\MiCartera\Domain\Exchange\Exchange;
use xVer\MiCartera\Domain\Exchange\ExchangeRepositoryInterface;

/**
 * @covers xVer\MiCartera\Domain\Exchange\Exchange
 */
class ExchangeTest extends TestCase
{
    /** @var EntityObjectRepositoryLoaderInterface&MockObject */
    private EntityObjectRepositoryLoaderInterface $repoLoader;
    /** @var ExchangeRepositoryInterface&MockObject */
    private ExchangeRepositoryInterface $repoExchange;

    public function setUp(): void
    {
        $this->repoExchange = $this->createMock(ExchangeRepositoryInterface::class);
        /** @var EntityObjectRepositoryLoaderInterface&Stub */
        $this->repoLoader = $this->createStub(EntityObjectRepositoryLoaderInterface::class);
        $this->repoLoader->method('load')->willReturn($this->repoExchange);
    }

    public function testDuplicateCodeThrowsException(): void
    {
        $this->repoExchange->method('findById')->willReturn($this->createStub(Exchange::class));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('ExchangeExists');
        new Exchange($this->repoLoader, 'CODE', 'NAME');
    }

    public function testExchangeValueObjectIsCreated(): void
    {
        $code = 'CODE';
        $name = "NAME";
        $exchange = new Exchange($this->repoLoader, $code, $name);
        $this->assertSame($code, $exchange->getCode());
        $this->assertSame($name, $exchange->getName());
        $this->assertTrue($exchange->sameId($exchange));
    }

    /** @dataProvider invalidCodes */
    public function testInvalidCodeThrowExceptions($testCode): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('stringLength');
        new Exchange($this->repoLoader, $testCode, 'NAME');
    }

    public static function invalidCodes(): array
    {
        return [
            [''],
            ['AAAAAAAAAAAAA']
        ];
    }

    /**
     * @dataProvider invalidNames
     */
    public function testExchangeNameFormat($name): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('stringLength');
        new Exchange($this->repoLoader, 'CODE', $name);
    }

    public static function invalidNames(): array
    {
        $name = '';
        for ($i=0; $i <256 ; $i++) { 
            $name .= mt_rand(0, 9);
        }
        return [
            [''], [$name]
        ];
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $exchange = new Exchange($this->repoLoader, 'CODE', 'NAME');
        $entity = new class implements EntityObjectInterface { public function sameId(EntityObjectInterface $otherEntity): bool { return true; }};
        $exchange->sameId($entity);
    }

    public function testExceptionIsThrownOnCommitFail(): void
    {
        $this->repoExchange->expects($this->once())->method('persist')->willThrowException(new Exception());        
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('actionFailed');
        new Exchange($this->repoLoader, 'CODE', 'NAME');
    }
}
