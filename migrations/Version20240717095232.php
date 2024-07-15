<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240717095232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transactionAdquisition DROP FOREIGN KEY FK_C776C3546E0F685C');
        $this->addSql('ALTER TABLE transactionAdquisition DROP FOREIGN KEY FK_C776C3549B6B5FBA');
        $this->addSql('ALTER TABLE transactionAdquisition DROP FOREIGN KEY FK_C776C354E6DFE496');
        $this->addSql('ALTER TABLE transactionLiquidation DROP FOREIGN KEY FK_D7BF7C016E0F685C');
        $this->addSql('ALTER TABLE transactionLiquidation DROP FOREIGN KEY FK_D7BF7C019B6B5FBA');
        $this->addSql('ALTER TABLE transactionLiquidation DROP FOREIGN KEY FK_D7BF7C01E6DFE496');
        $this->addSql('ALTER TABLE accountingMovement DROP FOREIGN KEY FK_406BEBEB7B862964');
        $this->addSql('ALTER TABLE accountingMovement DROP FOREIGN KEY FK_406BEBEB90140D4C');

        $this->addSql('RENAME TABLE transactionAdquisition TO stockTransactionAdquisition');
        $this->addSql('RENAME TABLE transactionLiquidation TO stockTransactionLiquidation');
        $this->addSql('RENAME TABLE accountingMovement TO stockAccountingMovement');

        $this->addSql('ALTER TABLE stockAccountingMovement ADD CONSTRAINT FK_A143218E7B862964 FOREIGN KEY (adquisition_id) REFERENCES stockTransactionAdquisition (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE stockAccountingMovement ADD CONSTRAINT FK_A143218E90140D4C FOREIGN KEY (liquidation_id) REFERENCES stockTransactionLiquidation (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE stockTransactionAdquisition ADD CONSTRAINT FK_DDE6ABB7E6DFE496 FOREIGN KEY (currency_iso3) REFERENCES currency (iso3)');
        $this->addSql('ALTER TABLE stockTransactionAdquisition ADD CONSTRAINT FK_DDE6ABB76E0F685C FOREIGN KEY (stock_code) REFERENCES stock (code)');
        $this->addSql('ALTER TABLE stockTransactionAdquisition ADD CONSTRAINT FK_DDE6ABB79B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE stockTransactionLiquidation ADD CONSTRAINT FK_CD2F14E2E6DFE496 FOREIGN KEY (currency_iso3) REFERENCES currency (iso3)');
        $this->addSql('ALTER TABLE stockTransactionLiquidation ADD CONSTRAINT FK_CD2F14E26E0F685C FOREIGN KEY (stock_code) REFERENCES stock (code)');
        $this->addSql('ALTER TABLE stockTransactionLiquidation ADD CONSTRAINT FK_CD2F14E29B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stockAccountingMovement DROP FOREIGN KEY FK_A143218E7B862964');
        $this->addSql('ALTER TABLE stockAccountingMovement DROP FOREIGN KEY FK_A143218E90140D4C');
        $this->addSql('ALTER TABLE stockTransactionAdquisition DROP FOREIGN KEY FK_DDE6ABB7E6DFE496');
        $this->addSql('ALTER TABLE stockTransactionAdquisition DROP FOREIGN KEY FK_DDE6ABB76E0F685C');
        $this->addSql('ALTER TABLE stockTransactionAdquisition DROP FOREIGN KEY FK_DDE6ABB79B6B5FBA');
        $this->addSql('ALTER TABLE stockTransactionLiquidation DROP FOREIGN KEY FK_CD2F14E2E6DFE496');
        $this->addSql('ALTER TABLE stockTransactionLiquidation DROP FOREIGN KEY FK_CD2F14E26E0F685C');
        $this->addSql('ALTER TABLE stockTransactionLiquidation DROP FOREIGN KEY FK_CD2F14E29B6B5FBA');

        $this->addSql('RENAME TABLE stockTransactionAdquisition TO transactionAdquisition');
        $this->addSql('RENAME TABLE stockTransactionLiquidation TO transactionLiquidation');
        $this->addSql('RENAME TABLE stockAccountingMovement TO accountingMovement');

        $this->addSql('ALTER TABLE transactionAdquisition ADD CONSTRAINT FK_C776C3546E0F685C FOREIGN KEY (stock_code) REFERENCES stock (code) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE transactionAdquisition ADD CONSTRAINT FK_C776C3549B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE transactionAdquisition ADD CONSTRAINT FK_C776C354E6DFE496 FOREIGN KEY (currency_iso3) REFERENCES currency (iso3) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE transactionLiquidation ADD CONSTRAINT FK_D7BF7C016E0F685C FOREIGN KEY (stock_code) REFERENCES stock (code) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE transactionLiquidation ADD CONSTRAINT FK_D7BF7C019B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE transactionLiquidation ADD CONSTRAINT FK_D7BF7C01E6DFE496 FOREIGN KEY (currency_iso3) REFERENCES currency (iso3) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE accountingMovement ADD CONSTRAINT FK_406BEBEB7B862964 FOREIGN KEY (adquisition_id) REFERENCES transactionAdquisition (id)');
        $this->addSql('ALTER TABLE accountingMovement ADD CONSTRAINT FK_406BEBEB90140D4C FOREIGN KEY (liquidation_id) REFERENCES transactionLiquidation (id) ON UPDATE NO ACTION');
    }
}
