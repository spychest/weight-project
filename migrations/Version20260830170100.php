<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830170100 extends AbstractMigration
{
    public function getDescription(): string { return 'Aligne les métadonnées datetime et le nom de l’index des identités.'; }
    public function isTransactional(): bool { return false; }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user CHANGE created_at created_at DATETIME NOT NULL, CHANGE last_login_at last_login_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user_identity CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user_identity RENAME INDEX IDX_86B0D3C7A76ED395 TO IDX_8A180DC4A76ED395');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_identity RENAME INDEX IDX_8A180DC4A76ED395 TO IDX_86B0D3C7A76ED395');
        $this->addSql("ALTER TABLE app_user CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE last_login_at last_login_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("ALTER TABLE user_identity CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }
}
