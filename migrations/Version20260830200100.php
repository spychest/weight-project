<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830200100 extends AbstractMigration
{
    public function getDescription(): string { return 'Aligne la colonne de préférence visuelle avec le mapping Doctrine.'; }
    public function isTransactional(): bool { return false; }
    public function up(Schema $schema): void { $this->addSql('ALTER TABLE app_user CHANGE dark_mode_enabled dark_mode_enabled TINYINT NOT NULL'); }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE app_user CHANGE dark_mode_enabled dark_mode_enabled TINYINT(1) DEFAULT 0 NOT NULL'); }
}
