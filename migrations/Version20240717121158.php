<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240717121158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stockAccountingMovement DROP FOREIGN KEY FK_A143218E7B862964');
        $this->addSql('DROP INDEX IDX_A143218E7B862964 ON stockAccountingMovement');
        $this->addSql('DROP INDEX `primary` ON stockAccountingMovement');
        $this->addSql('ALTER TABLE stockTransactionAdquisition DROP FOREIGN KEY FK_DDE6ABB76E0F685C');
        $this->addSql('ALTER TABLE stockTransactionAdquisition DROP FOREIGN KEY FK_DDE6ABB79B6B5FBA');
        $this->addSql('ALTER TABLE stockTransactionAdquisition DROP FOREIGN KEY FK_DDE6ABB7E6DFE496');
        $this->addSql('RENAME TABLE stockTransactionAdquisition TO stockTransactionAcquisition');
        $this->addSql('ALTER TABLE stockTransactionAcquisition ADD CONSTRAINT FK_3F3AB0CEE6DFE496 FOREIGN KEY (currency_iso3) REFERENCES currency (iso3)');
        $this->addSql('ALTER TABLE stockTransactionAcquisition ADD CONSTRAINT FK_3F3AB0CE6E0F685C FOREIGN KEY (stock_code) REFERENCES stock (code)');
        $this->addSql('ALTER TABLE stockTransactionAcquisition ADD CONSTRAINT FK_3F3AB0CE9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE stockAccountingMovement CHANGE adquisitionPrice acquisitionPrice NUMERIC(16, 4) UNSIGNED NOT NULL, CHANGE adquisition_expenses acquisition_expenses NUMERIC(10, 4) UNSIGNED NOT NULL, CHANGE adquisition_id acquisition_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE stockAccountingMovement ADD CONSTRAINT FK_A143218E6F52F3C FOREIGN KEY (acquisition_id) REFERENCES stockTransactionAcquisition (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_A143218E6F52F3C ON stockAccountingMovement (acquisition_id)');
        $this->addSql('ALTER TABLE stockAccountingMovement ADD PRIMARY KEY (acquisition_id, liquidation_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stockAccountingMovement DROP FOREIGN KEY FK_A143218E6F52F3C');
        $this->addSql('DROP INDEX IDX_A143218E6F52F3C ON stockAccountingMovement');
        $this->addSql('DROP INDEX `primary` ON stockAccountingMovement');
        $this->addSql('ALTER TABLE stockTransactionAcquisition DROP FOREIGN KEY FK_3F3AB0CEE6DFE496');
        $this->addSql('ALTER TABLE stockTransactionAcquisition DROP FOREIGN KEY FK_3F3AB0CE6E0F685C');
        $this->addSql('ALTER TABLE stockTransactionAcquisition DROP FOREIGN KEY FK_3F3AB0CE9B6B5FBA');
        $this->addSql('RENAME TABLE stockTransactionAcquisition TO stockTransactionAdquisition');
        $this->addSql('ALTER TABLE stockTransactionAcquisition ADD CONSTRAINT FK_DDE6ABB76E0F685C FOREIGN KEY (currency_iso3) REFERENCES currency (iso3)');
        $this->addSql('ALTER TABLE stockTransactionAcquisition ADD CONSTRAINT FK_DDE6ABB79B6B5FBA FOREIGN KEY (stock_code) REFERENCES stock (code)');
        $this->addSql('ALTER TABLE stockTransactionAcquisition ADD CONSTRAINT FK_DDE6ABB7E6DFE496 FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE stockAccountingMovement CHANGE acquisitionPrice adquisitionPrice NUMERIC(16, 4) UNSIGNED NOT NULL, CHANGE acquisition_expenses adquisition_expenses NUMERIC(10, 4) UNSIGNED NOT NULL, CHANGE acquisition_id adquisition_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE stockAccountingMovement ADD CONSTRAINT FK_A143218E6F52F3C FOREIGN KEY (acquisition_id) REFERENCES stockTransactionAcquisition (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_A143218E7B862964 ON stockAccountingMovement (acquisition_id)');
        $this->addSql('ALTER TABLE stockAccountingMovement ADD PRIMARY KEY (acquisition_id, liquidation_id)');
    }
}
