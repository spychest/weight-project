<?php

namespace App\Service\Shopping;

use App\Repository\IngredientCatalogItemRepository;

final class DatabaseIngredientCatalogProvider implements IngredientCatalogProviderInterface
{
    /** @var list<array{canonicalName: string, category: string, aliases: list<string>}>|null */
    private ?array $catalogEntries = null;

    public function __construct(
        private readonly IngredientCatalogItemRepository $ingredientCatalogItemRepository,
    ) {
    }

    public function getCatalogEntries(): array
    {
        if ($this->catalogEntries !== null) {
            return $this->catalogEntries;
        }

        $this->catalogEntries = [];
        foreach ($this->ingredientCatalogItemRepository->findAllOrderedByName() as $catalogItem) {
            $this->catalogEntries[] = [
                'canonicalName' => $catalogItem->getCanonicalName(),
                'category' => $catalogItem->getCategory(),
                'aliases' => $catalogItem->getAliases(),
            ];
        }

        return $this->catalogEntries;
    }
}
