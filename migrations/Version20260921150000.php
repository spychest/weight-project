<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921150000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute le nombre de portions aux recettes.'; }
    public function isTransactional(): bool { return false; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recipe ADD servings INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE recipe CHANGE servings servings INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recipe DROP servings');
    }
}
