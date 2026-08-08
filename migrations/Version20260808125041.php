<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260808125041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sleep_entry (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, bed_time TIME NOT NULL, wake_up_time TIME NOT NULL, quality INT NOT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, profile_id INT NOT NULL, INDEX IDX_B5D00BB0CCFA12B8 (profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE sleep_entry ADD CONSTRAINT FK_B5D00BB0CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sleep_entry DROP FOREIGN KEY FK_B5D00BB0CCFA12B8');
        $this->addSql('DROP TABLE sleep_entry');
    }
}
