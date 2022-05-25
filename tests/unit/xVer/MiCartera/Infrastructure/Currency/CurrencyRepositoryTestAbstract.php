<?php declare(strict_types=1);

namespace Tests\unit\xVer\MiCartera\Infrastructure\Currency;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryInterface;

abstract class CurrencyRepositoryTestAbstract extends KernelTestCase implements CurrencyRepositoryTestInterface
{
    protected static CurrencyRepositoryInterface $repo;
    protected static string $iso3;
    protected static Currency $currency;

    public function testCurrencyIsAdded(): CurrencyRepositoryInterface
    {
        $currency = self::$repo->add(self::$currency);
        $this->assertInstanceOf(Currency::class, $currency);
        return self::$repo;
    }

    /** @depends testCurrencyIsAdded */
    public function testCurrencyIsFoundById(CurrencyRepositoryInterface $repo): CurrencyRepositoryInterface
    {
        $currency = $repo->findById(self::$iso3);
        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertSame(self::$iso3, $currency->getIso3());
        return $repo;
    }

    /** @depends testCurrencyIsAdded */
    public function testAddingCurrencyWithExistingIso3ThrowsException(CurrencyRepositoryInterface $repo): void
    {
        $this->expectException(DomainException::class);
        $currency = $repo->add(self::$currency);
    }
}