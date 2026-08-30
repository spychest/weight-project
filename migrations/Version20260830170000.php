<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830170000 extends AbstractMigration
{
    public function isTransactional(): bool { return false; }
    public function getDescription(): string { return 'Ajoute les comptes, identités Google et le propriétaire facultatif du profil.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) DEFAULT NULL, email_verified TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_login_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_88BDF3E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_identity (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, provider VARCHAR(30) NOT NULL, provider_user_id VARCHAR(255) NOT NULL, provider_email VARCHAR(180) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_86B0D3C7A76ED395 (user_id), UNIQUE INDEX unique_provider_identity (provider, provider_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_identity ADD CONSTRAINT FK_86B0D3C7A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE profile ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE profile ADD CONSTRAINT FK_8157AA0FA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8157AA0FA76ED395 ON profile (user_id)');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_identity DROP FOREIGN KEY FK_86B0D3C7A76ED395');
        $this->addSql('ALTER TABLE profile DROP FOREIGN KEY FK_8157AA0FA76ED395');
        $this->addSql('DROP TABLE user_identity');
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP INDEX UNIQ_8157AA0FA76ED395 ON profile');
        $this->addSql('ALTER TABLE profile DROP user_id');
    }
}
