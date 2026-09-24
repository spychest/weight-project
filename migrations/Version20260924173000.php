<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Précise le classement des conserves, légumineuses sèches et produits surgelés.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->updateCatalogItem(
            'haricot blanc',
            'Conserves et bocaux',
            ['haricots blancs', 'haricot blanc en conserve', 'haricots blancs en conserve'],
        );
        $this->updateCatalogItem(
            'haricot rouge',
            'Conserves et bocaux',
            ['haricots rouges', 'haricot rouge en conserve', 'haricots rouges en conserve'],
        );
        $this->updateCatalogItem(
            'pois chiche',
            'Conserves et bocaux',
            ['pois chiches', 'pois chiche en conserve', 'pois chiches en conserve'],
        );
        $this->updateCatalogItem(
            'tomate concassée',
            'Conserves et bocaux',
            ['tomate concassé', 'tomates concassées', 'tomates concassés', 'tomate concassée en conserve'],
        );
        $this->updateCatalogItem(
            'produit surgelé',
            'Surgelés',
            ['surgelé', 'surgelée', 'surgelés', 'surgelées'],
        );
    }

    public function down(Schema $schema): void
    {
        $this->updateCatalogItem('haricot blanc', 'Féculents et légumineuses', []);
        $this->updateCatalogItem('haricot rouge', 'Féculents et légumineuses', []);
        $this->updateCatalogItem('pois chiche', 'Féculents et légumineuses', []);
        $this->updateCatalogItem('tomate concassée', 'Conserves et bocaux', ['tomate concassé']);
        $this->updateCatalogItem('produit surgelé', 'Surgelés', ['surgelé']);
    }

    /** @param list<string> $aliases */
    private function updateCatalogItem(string $normalizedName, string $category, array $aliases): void
    {
        $this->addSql(
            'UPDATE ingredient_catalog_item '
            .'SET category = :category, aliases = :aliases '
            .'WHERE normalized_name = :normalizedName',
            [
                'category' => $category,
                'aliases' => json_encode($aliases, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'normalizedName' => $normalizedName,
            ],
        );
    }
}
