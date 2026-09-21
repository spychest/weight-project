<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921090000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les recettes partagées et leur compteur de vues uniques.'; }
    public function isTransactional(): bool { return false; }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE recipe (id INT AUTO_INCREMENT NOT NULL, profile_id INT NOT NULL, title VARCHAR(255) NOT NULL, preparation_time_minutes INT DEFAULT NULL, cooking_time_minutes INT DEFAULT NULL, photo_filename VARCHAR(255) DEFAULT NULL, utensils JSON NOT NULL, ingredients JSON NOT NULL, setup_steps JSON NOT NULL, preparation_steps JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_DA88B137CCFA12B8 (profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipe_view (id INT AUTO_INCREMENT NOT NULL, recipe_id INT NOT NULL, profile_id INT NOT NULL, viewed_at DATETIME NOT NULL, INDEX IDX_81E6F15159D8A214 (recipe_id), INDEX IDX_81E6F151CCFA12B8 (profile_id), UNIQUE INDEX unique_recipe_profile_view (recipe_id, profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE recipe ADD CONSTRAINT FK_DA88B137CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_view ADD CONSTRAINT FK_EE2BD71259D8A214 FOREIGN KEY (recipe_id) REFERENCES recipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_view ADD CONSTRAINT FK_EE2BD712CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE recipe_view');
        $this->addSql('DROP TABLE recipe');
    }
}
