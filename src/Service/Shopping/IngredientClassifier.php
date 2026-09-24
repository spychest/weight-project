<?php

namespace App\Service\Shopping;

final class IngredientClassifier
{
    public function __construct(
        private readonly IngredientCatalogProviderInterface $ingredientCatalogProvider,
    ) {
    }

    public function normalizeName(string $ingredientName): string
    {
        $normalizedName = mb_strtolower(trim($ingredientName));
        $normalizedName = preg_replace('/\s+/u', ' ', $normalizedName) ?? $normalizedName;

        return trim($normalizedName, " \t\n\r\0\x0B.,;:");
    }

    public function classify(string $ingredientName): string
    {
        $normalizedIngredientName = $this->normalizeName($ingredientName);
        $bestCategory = 'Autres';
        $longestMatchingTermLength = 0;

        foreach ($this->ingredientCatalogProvider->getCatalogEntries() as $catalogEntry) {
            $terms = [$catalogEntry['canonicalName'], ...$catalogEntry['aliases']];
            foreach ($terms as $term) {
                $normalizedTerm = $this->normalizeName($term);
                $termLength = mb_strlen($normalizedTerm);

                if (
                    $termLength > $longestMatchingTermLength
                    && ($normalizedIngredientName === $normalizedTerm
                        || str_contains($normalizedIngredientName, $normalizedTerm))
                ) {
                    $bestCategory = $catalogEntry['category'];
                    $longestMatchingTermLength = $termLength;
                }
            }
        }

        return $bestCategory;
    }
}
