<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260807072109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE daily_checkin ADD mood_level INT NOT NULL, ADD energy_level INT NOT NULL, ADD frustration_level INT NOT NULL, ADD note LONGTEXT DEFAULT NULL, ADD created_at DATETIME NOT NULL, DROP mood, DROP energy, DROP frustration');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE daily_checkin ADD mood INT NOT NULL, ADD energy INT NOT NULL, ADD frustration INT NOT NULL, DROP mood_level, DROP energy_level, DROP frustration_level, DROP note, DROP created_at');
    }
}
