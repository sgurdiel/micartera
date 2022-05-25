<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class VersionInitial extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', currency_iso3 VARCHAR(3) NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, timezone VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_7D3656A4E7927C74 (email), INDEX IDX_7D3656A4E6DFE496 (currency_iso3), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE accountingMovement (adquisition_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', liquidation_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', amount INT UNSIGNED NOT NULL, adquisitionPrice NUMERIC(16, 4) UNSIGNED NOT NULL, liquidationPrice NUMERIC(16, 4) UNSIGNED NOT NULL, adquisition_expenses NUMERIC(10, 4) UNSIGNED NOT NULL, liquidation_expenses NUMERIC(10, 4) UNSIGNED NOT NULL, INDEX IDX_406BEBEB7B862964 (adquisition_id), INDEX IDX_406BEBEB90140D4C (liquidation_id), PRIMARY KEY(adquisition_id, liquidation_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE currency (iso3 VARCHAR(3) NOT NULL, symbol VARCHAR(10) NOT NULL, decimals SMALLINT NOT NULL, PRIMARY KEY(iso3)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stock (code VARCHAR(4) NOT NULL, currency_iso3 VARCHAR(3) NOT NULL, name VARCHAR(255) NOT NULL, price NUMERIC(10, 4) UNSIGNED NOT NULL, INDEX IDX_4B365660E6DFE496 (currency_iso3), PRIMARY KEY(code)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transactionAdquisition (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', currency_iso3 VARCHAR(3) NOT NULL, stock_code VARCHAR(4) NOT NULL, account_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', datetimeutc DATETIME NOT NULL, amount INT UNSIGNED NOT NULL, amount_outstanding INT UNSIGNED NOT NULL, price NUMERIC(10, 4) UNSIGNED NOT NULL, expenses NUMERIC(10, 4) UNSIGNED NOT NULL, expenses_unaccounted_for NUMERIC(10, 4) UNSIGNED NOT NULL, INDEX IDX_C776C354E6DFE496 (currency_iso3), INDEX IDX_C776C3546E0F685C (stock_code), INDEX IDX_C776C3549B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transactionLiquidation (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', currency_iso3 VARCHAR(3) NOT NULL, stock_code VARCHAR(4) NOT NULL, account_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', datetimeutc DATETIME NOT NULL, amount INT UNSIGNED NOT NULL, price NUMERIC(10, 4) UNSIGNED NOT NULL, expenses NUMERIC(10, 4) UNSIGNED NOT NULL, INDEX IDX_D7BF7C01E6DFE496 (currency_iso3), INDEX IDX_D7BF7C016E0F685C (stock_code), INDEX IDX_D7BF7C019B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A4E6DFE496 FOREIGN KEY (currency_iso3) REFERENCES currency (iso3)');
        $this->addSql('ALTER TABLE accountingMovement ADD CONSTRAINT FK_406BEBEB7B862964 FOREIGN KEY (adquisition_id) REFERENCES transactionAdquisition (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE accountingMovement ADD CONSTRAINT FK_406BEBEB90140D4C FOREIGN KEY (liquidation_id) REFERENCES transactionLiquidation (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660E6DFE496 FOREIGN KEY (currency_iso3) REFERENCES currency (iso3)');
        $this->addSql('ALTER TABLE transactionAdquisition ADD CONSTRAINT FK_C776C354E6DFE496 FOREIGN KEY (currency_iso3) REFERENCES currency (iso3)');
        $this->addSql('ALTER TABLE transactionAdquisition ADD CONSTRAINT FK_C776C3546E0F685C FOREIGN KEY (stock_code) REFERENCES stock (code)');
        $this->addSql('ALTER TABLE transactionAdquisition ADD CONSTRAINT FK_C776C3549B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE transactionLiquidation ADD CONSTRAINT FK_D7BF7C01E6DFE496 FOREIGN KEY (currency_iso3) REFERENCES currency (iso3)');
        $this->addSql('ALTER TABLE transactionLiquidation ADD CONSTRAINT FK_D7BF7C016E0F685C FOREIGN KEY (stock_code) REFERENCES stock (code)');
        $this->addSql('ALTER TABLE transactionLiquidation ADD CONSTRAINT FK_D7BF7C019B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('CREATE TABLE session (sess_id varbinary(128) NOT NULL, sess_data blob NOT NULL, sess_lifetime int unsigned NOT NULL, sess_time int unsigned NOT NULL, PRIMARY KEY (sess_id), KEY sessions_sess_lifetime_idx (sess_lifetime)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO currency (iso3, symbol, decimals) VALUES ("EUR", "€", "2")');
        $this->addSql('INSERT INTO currency (iso3, symbol, decimals) VALUES ("USD", "$", "2")');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account DROP FOREIGN KEY FK_7D3656A4E6DFE496');
        $this->addSql('ALTER TABLE accountingMovement DROP FOREIGN KEY FK_406BEBEB7B862964');
        $this->addSql('ALTER TABLE accountingMovement DROP FOREIGN KEY FK_406BEBEB90140D4C');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660E6DFE496');
        $this->addSql('ALTER TABLE transactionAdquisition DROP FOREIGN KEY FK_C776C354E6DFE496');
        $this->addSql('ALTER TABLE transactionAdquisition DROP FOREIGN KEY FK_C776C3546E0F685C');
        $this->addSql('ALTER TABLE transactionAdquisition DROP FOREIGN KEY FK_C776C3549B6B5FBA');
        $this->addSql('ALTER TABLE transactionLiquidation DROP FOREIGN KEY FK_D7BF7C01E6DFE496');
        $this->addSql('ALTER TABLE transactionLiquidation DROP FOREIGN KEY FK_D7BF7C016E0F685C');
        $this->addSql('ALTER TABLE transactionLiquidation DROP FOREIGN KEY FK_D7BF7C019B6B5FBA');
        $this->addSql('DROP TABLE account');
        $this->addSql('DROP TABLE accountingMovement');
        $this->addSql('DROP TABLE currency');
        $this->addSql('DROP TABLE stock');
        $this->addSql('DROP TABLE transactionAdquisition');
        $this->addSql('DROP TABLE transactionLiquidation');
        $this->addSql('DROP TABLE session');
    }
}
