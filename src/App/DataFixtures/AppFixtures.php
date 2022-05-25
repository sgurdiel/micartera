<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Transaction\Transaction;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $currencyEuro = new Currency('EUR', '€', 2);
        $manager->persist($currencyEuro);
        $currency = new Currency('USD', '$', 2);
        $manager->persist($currency);
        $account = new Account('test@example.com', 'password1', $currencyEuro, new \DateTimeZone("Europe/Madrid"), ['ROLE_USER']);
        $manager->persist($account);
        $account2 = new Account('test_other@example.com', 'password2', $currencyEuro, new \DateTimeZone("America/Chicago"), ['ROLE_USER']);
        $manager->persist($account2);
        $price = new StockPriceVO('2.5620', $currencyEuro);
        $stock = new Stock('CABK', 'Caixabank', $price);
        $manager->persist($stock);
        $price2 = new StockPriceVO('3.5620', $currencyEuro);
        $stock2 = new Stock('SAN', 'Santander', $price2);
        $manager->persist($stock2);
        $expenses = new MoneyVO('10.23', $currencyEuro);
        $transaction = new Transaction(Transaction::TYPE_BUY, $stock, new \DateTime('2021-09-20 12:09:03', new \DateTimeZone('UTC')), 200, $expenses, $account);
        $manager->persist($transaction);
        $price3 = new StockPriceVO('5.9620', $currencyEuro);
        $stock3 = new Stock('ROVI', 'Laboratorios Rovi', $price3);
        $manager->persist($stock3);
        $manager->flush();
    }
}
