<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924170500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le catalogue administrable des ingrédients.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE ingredient_catalog_item ('
            .'id INT AUTO_INCREMENT NOT NULL, '
            .'canonical_name VARCHAR(160) NOT NULL, '
            .'normalized_name VARCHAR(160) NOT NULL, '
            .'category VARCHAR(100) NOT NULL, '
            .'aliases JSON NOT NULL, '
            .'UNIQUE INDEX UNIQ_INGREDIENT_CATALOG_NORMALIZED_NAME (normalized_name), '
            .'PRIMARY KEY(id)'
            .') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ingredient_catalog_item');
    }
}
