<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240717122137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stockTransactionAcquisition RENAME INDEX idx_dde6abb7e6dfe496 TO IDX_3F3AB0CEE6DFE496');
        $this->addSql('ALTER TABLE stockTransactionAcquisition RENAME INDEX idx_dde6abb76e0f685c TO IDX_3F3AB0CE6E0F685C');
        $this->addSql('ALTER TABLE stockTransactionAcquisition RENAME INDEX idx_dde6abb79b6b5fba TO IDX_3F3AB0CE9B6B5FBA');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stockTransactionAcquisition RENAME INDEX idx_3f3ab0cee6dfe496 TO IDX_DDE6ABB7E6DFE496');
        $this->addSql('ALTER TABLE stockTransactionAcquisition RENAME INDEX idx_3f3ab0ce9b6b5fba TO IDX_DDE6ABB79B6B5FBA');
        $this->addSql('ALTER TABLE stockTransactionAcquisition RENAME INDEX idx_3f3ab0ce6e0f685c TO IDX_DDE6ABB76E0F685C');
    }
}
