<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830200000 extends AbstractMigration
{
    public function getDescription(): string { return 'Enregistre la préférence de mode nuit de chaque utilisateur.'; }
    public function isTransactional(): bool { return false; }
    public function up(Schema $schema): void { $this->addSql('ALTER TABLE app_user ADD dark_mode_enabled TINYINT NOT NULL'); }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE app_user DROP dark_mode_enabled'); }
}
