<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les indicateurs végétarien, végan et sans gluten aux recettes.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recipe ADD vegetarian TINYINT(1) DEFAULT 0 NOT NULL, ADD vegan TINYINT(1) DEFAULT 0 NOT NULL, ADD gluten_free TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recipe DROP vegetarian, DROP vegan, DROP gluten_free');
    }
}
