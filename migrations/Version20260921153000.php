<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921153000 extends AbstractMigration
{
    public function getDescription(): string { return 'Renomme les durées des recettes en mise en place et préparation.'; }
    public function isTransactional(): bool { return false; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recipe CHANGE preparation_time_minutes setup_time_minutes INT DEFAULT NULL, CHANGE cooking_time_minutes preparation_time_minutes INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recipe CHANGE setup_time_minutes preparation_time_minutes INT DEFAULT NULL, CHANGE preparation_time_minutes cooking_time_minutes INT DEFAULT NULL');
    }
}
