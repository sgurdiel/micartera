<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Domain\Currency;

use PHPUnit\Framework\TestCase;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;

/**
 * @covers xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\Bundle\DomainBundle\Domain\DomainException
 * @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 */
class CurrencyTest extends TestCase
{
    public function testCurrencyValueObjectIsCreated(): void
    {
        $iso3 = 'EUR';
        $symbol = "€";
        $decimals = 2;
        $curreny = new Currency($iso3, $symbol, $decimals);
        $this->assertSame($iso3, $curreny->getISO3());
        $this->assertSame($symbol, $curreny->getSymbol());
        $this->assertSame($decimals, $curreny->getDecimals());
        $this->assertTrue($curreny->sameId($curreny));
    }

    public function testInvalidArgumentsThrowExceptions(): void
    {
        $testCases = ['', 'ABCD'];
        $exceptionsExpected = count($testCases);
        $exceptionsThrown = 0;
        foreach ($testCases as $testCase) {
            try {
                $aux = new Currency($testCase, '€', 2);
                unset($aux);
            } catch (\DomainException $th) {
                $exceptionsThrown++;
            }
        }
        try {
            $aux = new Currency('ABC', '', 2); unset($aux);
        } catch (\DomainException $th) { $exceptionsThrown++; }
        try {
            $aux = new Currency('ABC', '€', -2); unset($aux);
        } catch (\DomainException $th) { $exceptionsThrown++; }
        $exceptionsExpected += 2;
        $this->assertSame($exceptionsExpected, $exceptionsThrown);
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $currency = new Currency('EUR', '€', 2);
        $price = new StockPriceVO('4.5600', $currency);
        $stock = new Stock('ABCD', 'ABCD Name', $price);
        $currency->sameId($stock);
    }
}
