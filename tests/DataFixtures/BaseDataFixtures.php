<?php

namespace Tests\DataFixtures;

use DateTime;
use DateTimeZone;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Stock\Transaction\Acquisition;

class BaseDataFixtures extends Fixture
{
    private EntityObjectRepositoryLoader $repoLoader;

    public function __construct(private ManagerRegistry $registry)
    {
        $this->repoLoader = EntityObjectRepositoryLoader::doctrine($this->registry);
    }

    public function load(ObjectManager $manager): void
    {
        $currencyEuro = new Currency($this->repoLoader, 'EUR', '€', 2);
        new Currency($this->repoLoader, 'USD', '$', 2);
        $account = new Account(
            $this->repoLoader,
            'test@example.com',
            'password1',
            $currencyEuro,
            new DateTimeZone("Europe/Madrid"),
            ['ROLE_USER']
        );
        new Account(
            $this->repoLoader,
            'test_other@example.com',
            'password2',
            $currencyEuro,
            new DateTimeZone("America/Chicago"),
            ['ROLE_USER']
        );
        $price = new StockPriceVO('2.5620', $currencyEuro);
        $stock = new Stock($this->repoLoader, 'CABK', 'Caixabank', $price);
        $price2 = new StockPriceVO('3.5620', $currencyEuro);
        new Stock($this->repoLoader, 'SAN', 'Santander', $price2);
        $price3 = new StockPriceVO('5.9620', $currencyEuro);
        new Stock($this->repoLoader, 'ROVI', 'Laboratorios Rovi', $price3);
        $expenses = new MoneyVO('10.23', $currencyEuro);
        new Acquisition(
            $this->repoLoader,
            $stock,
            new DateTime('last week', new DateTimeZone('UTC')),
            200,
            $expenses,
            $account
        );
    }
}
