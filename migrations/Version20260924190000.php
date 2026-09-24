<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le nom d’affichage et les avatars des profils et identités Google.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profile ADD display_name VARCHAR(100) DEFAULT NULL, ADD avatar_filename VARCHAR(255) DEFAULT NULL, ADD google_avatar_url VARCHAR(2048) DEFAULT NULL');
        $this->addSql("UPDATE profile SET display_name = CASE WHEN biological_gender = 'female' THEN 'Jane Doe' ELSE 'John Doe' END");
        $this->addSql('ALTER TABLE profile MODIFY display_name VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE user_identity ADD provider_avatar_url VARCHAR(2048) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profile DROP display_name, DROP avatar_filename, DROP google_avatar_url');
        $this->addSql('ALTER TABLE user_identity DROP provider_avatar_url');
    }
}
