<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250101201550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stock ADD exchange VARCHAR(12) NOT NULL DEFAULT \'BME\'');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660D33BB079 FOREIGN KEY (exchange) REFERENCES exchange (code)');
        $this->addSql('CREATE INDEX IDX_4B365660D33BB079 ON stock (exchange)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660D33BB079');
        $this->addSql('DROP INDEX IDX_4B365660D33BB079 ON stock');
        $this->addSql('ALTER TABLE stock DROP exchange');
    }
}
