<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le catalogue des ingrédients, les listes de courses et leurs recettes sources.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE shopping_list (id INT AUTO_INCREMENT NOT NULL, profile_id INT NOT NULL, active TINYINT(1) NOT NULL, items JSON NOT NULL, excluded_generated_item_keys JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_recipe_added_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_78F751A5CCFA12B8 (profile_id), INDEX idx_shopping_list_active (profile_id, active), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shopping_list_recipe (id INT AUTO_INCREMENT NOT NULL, shopping_list_id INT NOT NULL, recipe_id INT DEFAULT NULL, recipe_title VARCHAR(255) NOT NULL, ingredient_snapshot JSON NOT NULL, preparation_count INT NOT NULL, INDEX IDX_8892EC3A6F55A77B (shopping_list_id), INDEX IDX_8892EC3A59D8A214 (recipe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE shopping_list ADD CONSTRAINT FK_78F751A5CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shopping_list_recipe ADD CONSTRAINT FK_8892EC3A6F55A77B FOREIGN KEY (shopping_list_id) REFERENCES shopping_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shopping_list_recipe ADD CONSTRAINT FK_8892EC3A59D8A214 FOREIGN KEY (recipe_id) REFERENCES recipe (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE shopping_list_recipe');
        $this->addSql('DROP TABLE shopping_list');
    }
}
