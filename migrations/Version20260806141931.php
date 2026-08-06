<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806141931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE milestone ADD profile_id INT NOT NULL');
        $this->addSql('ALTER TABLE milestone ADD CONSTRAINT FK_4FAC8382CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
        $this->addSql('CREATE INDEX IDX_4FAC8382CCFA12B8 ON milestone (profile_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE milestone DROP FOREIGN KEY FK_4FAC8382CCFA12B8');
        $this->addSql('DROP INDEX IDX_4FAC8382CCFA12B8 ON milestone');
        $this->addSql('ALTER TABLE milestone DROP profile_id');
    }
}
