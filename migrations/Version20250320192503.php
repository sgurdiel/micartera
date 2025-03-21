<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250320192503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stockAccountingMovement CHANGE amount amount NUMERIC(18, 9) UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE stockTransactionAcquisition CHANGE amount amount NUMERIC(18, 9) UNSIGNED NOT NULL, CHANGE amount_outstanding amount_outstanding NUMERIC(18, 9) UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE stockTransactionLiquidation CHANGE amount amount NUMERIC(18, 9) UNSIGNED NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stockAccountingMovement CHANGE amount amount INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE stockTransactionLiquidation CHANGE amount amount INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE stockTransactionAcquisition CHANGE amount amount INT UNSIGNED NOT NULL, CHANGE amount_outstanding amount_outstanding INT UNSIGNED NOT NULL');
    }
}
