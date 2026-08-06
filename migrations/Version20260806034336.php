<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806034336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE daily_checkin ADD profile_id INT NOT NULL');
        $this->addSql('ALTER TABLE daily_checkin ADD CONSTRAINT FK_3E82CD65CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
        $this->addSql('CREATE INDEX IDX_3E82CD65CCFA12B8 ON daily_checkin (profile_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE daily_checkin DROP FOREIGN KEY FK_3E82CD65CCFA12B8');
        $this->addSql('DROP INDEX IDX_3E82CD65CCFA12B8 ON daily_checkin');
        $this->addSql('ALTER TABLE daily_checkin DROP profile_id');
    }
}
