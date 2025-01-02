<?php declare(strict_types=1);

namespace Tests\unit\Domain\Exchange;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Exchange\Exchange;
use xVer\MiCartera\Domain\Exchange\ExchangesCollection;

/**
 * @covers xVer\MiCartera\Domain\Exchange\ExchangesCollection
 */
class ExchangesCollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $exchangesCollection = new ExchangesCollection([]);
        $this->assertSame(Exchange::class, $exchangesCollection->type());
    }
}
