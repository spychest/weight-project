<?php

namespace App\Tests\Unit\Service;

use App\Service\Shopping\IngredientCatalogProviderInterface;

final class InMemoryIngredientCatalogProvider implements IngredientCatalogProviderInterface
{
    /**
     * @param list<array{canonicalName: string, category: string, aliases: list<string>}> $catalogEntries
     */
    public function __construct(
        private readonly array $catalogEntries,
    ) {
    }

    public function getCatalogEntries(): array
    {
        return $this->catalogEntries;
    }
}
