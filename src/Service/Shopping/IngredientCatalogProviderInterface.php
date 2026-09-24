<?php

namespace App\Service\Shopping;

interface IngredientCatalogProviderInterface
{
    /** @return list<array{canonicalName: string, category: string, aliases: list<string>}> */
    public function getCatalogEntries(): array;
}
