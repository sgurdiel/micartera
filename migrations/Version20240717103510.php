<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240717103510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stockAccountingMovement RENAME INDEX idx_406bebeb7b862964 TO IDX_A143218E7B862964');
        $this->addSql('ALTER TABLE stockAccountingMovement RENAME INDEX idx_406bebeb90140d4c TO IDX_A143218E90140D4C');
        $this->addSql('ALTER TABLE stockTransactionAdquisition RENAME INDEX idx_c776c354e6dfe496 TO IDX_DDE6ABB7E6DFE496');
        $this->addSql('ALTER TABLE stockTransactionAdquisition RENAME INDEX idx_c776c3546e0f685c TO IDX_DDE6ABB76E0F685C');
        $this->addSql('ALTER TABLE stockTransactionAdquisition RENAME INDEX idx_c776c3549b6b5fba TO IDX_DDE6ABB79B6B5FBA');
        $this->addSql('ALTER TABLE stockTransactionLiquidation RENAME INDEX idx_d7bf7c01e6dfe496 TO IDX_CD2F14E2E6DFE496');
        $this->addSql('ALTER TABLE stockTransactionLiquidation RENAME INDEX idx_d7bf7c016e0f685c TO IDX_CD2F14E26E0F685C');
        $this->addSql('ALTER TABLE stockTransactionLiquidation RENAME INDEX idx_d7bf7c019b6b5fba TO IDX_CD2F14E29B6B5FBA');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stockAccountingMovement RENAME INDEX idx_a143218e90140d4c TO IDX_406BEBEB90140D4C');
        $this->addSql('ALTER TABLE stockAccountingMovement RENAME INDEX idx_a143218e7b862964 TO IDX_406BEBEB7B862964');
        $this->addSql('ALTER TABLE stockTransactionAdquisition RENAME INDEX idx_dde6abb7e6dfe496 TO IDX_C776C354E6DFE496');
        $this->addSql('ALTER TABLE stockTransactionAdquisition RENAME INDEX idx_dde6abb79b6b5fba TO IDX_C776C3549B6B5FBA');
        $this->addSql('ALTER TABLE stockTransactionAdquisition RENAME INDEX idx_dde6abb76e0f685c TO IDX_C776C3546E0F685C');
        $this->addSql('ALTER TABLE stockTransactionLiquidation RENAME INDEX idx_cd2f14e2e6dfe496 TO IDX_D7BF7C01E6DFE496');
        $this->addSql('ALTER TABLE stockTransactionLiquidation RENAME INDEX idx_cd2f14e29b6b5fba TO IDX_D7BF7C019B6B5FBA');
        $this->addSql('ALTER TABLE stockTransactionLiquidation RENAME INDEX idx_cd2f14e26e0f685c TO IDX_D7BF7C016E0F685C');
    }
}
