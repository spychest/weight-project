<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806034550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE food_event ADD profile_id INT NOT NULL');
        $this->addSql('ALTER TABLE food_event ADD CONSTRAINT FK_CACFCF4DCCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
        $this->addSql('CREATE INDEX IDX_CACFCF4DCCFA12B8 ON food_event (profile_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE food_event DROP FOREIGN KEY FK_CACFCF4DCCFA12B8');
        $this->addSql('DROP INDEX IDX_CACFCF4DCCFA12B8 ON food_event');
        $this->addSql('ALTER TABLE food_event DROP profile_id');
    }
}
