<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les motivations personnelles ordonnées des profils.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE motivation_point (id INT AUTO_INCREMENT NOT NULL, profile_id INT NOT NULL, content VARCHAR(500) NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_799A9290CCFA12B8 (profile_id), INDEX idx_motivation_point_profile_position (profile_id, position), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE motivation_point ADD CONSTRAINT FK_799A9290CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE motivation_point');
    }
}
