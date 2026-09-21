<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921160000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les astuces facultatives aux recettes.'; }
    public function isTransactional(): bool { return false; }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE recipe ADD tips JSON NOT NULL DEFAULT ('[]')");
        $this->addSql('ALTER TABLE recipe CHANGE tips tips JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recipe DROP tips');
    }
}
