<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250101194331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exchange (name VARCHAR(255) NOT NULL, code VARCHAR(12) NOT NULL, PRIMARY KEY(code)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'BCBA\',\'Buenos Aires Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'BMV\',\'Mexican Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'BVMF\',\'B3 - Brazil Stock Exchange and Over-the-Counter Market\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'CNSX\',\'Canadian Securities Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'CVE\',\'Toronto TSX Ventures Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'NASDAQ\',\'NASDAQ Last Sale\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'NYSE\',\'NYSE\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'NYSEARCA\',\'NYSE ARCA\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'NYSEAMERICAN\',\'NYSE American\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'OPRA\',\'Options Price Reporting Authority\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'OTCMKTS\',\'FINRA Other OTC Issues\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'TSE\',\'Toronto Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'TSX\',\'Toronto Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'TSXV\',\'Toronto TSX Ventures Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'AMS\',\'Euronext Amsterdam\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'BIT\',\'Borsa Italiana Milan Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'BME\',\'Bolsas y Mercados Españoles\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'CPH\',\'NASDAQ OMX Copenhagen\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'EBR\',\'Euronext Brussels\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'ELI\',\'Euronext Lisbon\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'EPA\',\'Euronext Paris\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'ETR\',\'Deutsche Börse XETRA\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'FRA\',\'Deutsche Börse Frankfurt Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'HEL\',\'NASDAQ OMX Helsinki\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'ICE\',\'NASDAQ OMX Iceland\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'IST\',\'Borsa Istanbul\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'LON\',\'London Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'RSE\',\'NASDAQ OMX Riga\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'STO\',\'NASDAQ OMX Stockholm\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'SWX\',\' VTX	SIX Swiss Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'TAL\',\'NASDAQ OMX Tallinn\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'VIE\',\'Vienna Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'VSE\',\'NASDAQ OMX Vilnius\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'WSE\',\'Warsaw Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'JSE\',\'Johannesburg Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'TADAWUL\',\'Saudi Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'TLV\',\'Tel Aviv Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'BKK\',\'Thailand Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'BOM\',\'Bombay Stock Exchange Limited\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'KLSE\',\'Bursa Malaysia\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'HKG\',\'Hong Kong Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'IDX\',\'Indonesia Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'KOSDAQ\',\'KOSDAQ\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'KRX\',\'Korea Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'NSE\',\'National Stock Exchange of India\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'SGX\',\'Singapore Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'SHA\',\'Shanghai Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'SHE\',\'Shenzhen Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'TPE\',\'Taiwan Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'TYO\',\'Tokyo Stock Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'ASX\',\'Australian Securities Exchange\')');
        $this->addSql('INSERT INTO exchange (code, name) VALUES (\'NZE\',\'New Zealand Stock Exchange\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE exchange');
    }
}
