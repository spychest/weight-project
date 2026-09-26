<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926180000 extends AbstractMigration
{
    public function isTransactional(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return 'Ajoute les préférences de notification et les abonnements Web Push.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE app_user ADD notifications_enabled TINYINT(1) DEFAULT 0 NOT NULL, ADD notification_frequency VARCHAR(20) DEFAULT 'weekly' NOT NULL, ADD last_notification_sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("CREATE TABLE push_subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, endpoint LONGTEXT NOT NULL, endpoint_hash VARCHAR(64) NOT NULL, public_key VARCHAR(255) NOT NULL, authentication_token VARCHAR(255) NOT NULL, content_encoding VARCHAR(30) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_6D3A7F0AA76ED395 (user_id), UNIQUE INDEX uniq_push_subscription_endpoint_hash (endpoint_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE push_subscription ADD CONSTRAINT FK_6D3A7F0AA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE push_subscription');
        $this->addSql('ALTER TABLE app_user DROP notifications_enabled, DROP notification_frequency, DROP last_notification_sent_at');
    }
}
