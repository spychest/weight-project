<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260807070207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE drink_entry (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, drink_type VARCHAR(255) NOT NULL, quantity INT NOT NULL, description VARCHAR(255) DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, profile_id INT NOT NULL, INDEX IDX_D5FD4CD5CCFA12B8 (profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE drink_entry ADD CONSTRAINT FK_D5FD4CD5CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE drink_entry DROP FOREIGN KEY FK_D5FD4CD5CCFA12B8');
        $this->addSql('DROP TABLE drink_entry');
    }
}
