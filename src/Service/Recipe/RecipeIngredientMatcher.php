<?php

namespace App\Service\Recipe;

use App\Entity\Recipe;
use App\Service\Shopping\IngredientClassifier;

final class RecipeIngredientMatcher
{
    public const MATCH_ALL = 'all';
    public const MATCH_ANY = 'any';

    public function __construct(
        private readonly IngredientClassifier $ingredientClassifier,
    ) {
    }

    /**
     * @param list<string> $requiredIngredients
     * @param list<string> $excludedIngredients
     */
    public function matches(
        Recipe $recipe,
        array $requiredIngredients,
        array $excludedIngredients,
        string $matchingMode = self::MATCH_ALL,
    ): bool {
        $recipeIngredientKeys = [];
        foreach ($recipe->getIngredients() as $ingredient) {
            $ingredientName = trim((string) $ingredient['name']);
            if ($ingredientName === '') {
                continue;
            }

            $recipeIngredientKeys[] = $this->buildIngredientKey($ingredientName);
        }
        $recipeIngredientKeys = array_values(array_unique($recipeIngredientKeys));

        $requiredIngredientKeys = array_map($this->buildIngredientKey(...), $requiredIngredients);
        $excludedIngredientKeys = array_map($this->buildIngredientKey(...), $excludedIngredients);

        if (array_intersect($recipeIngredientKeys, $excludedIngredientKeys) !== []) {
            return false;
        }

        if ($requiredIngredientKeys === []) {
            return true;
        }

        $matchingRequiredIngredientCount = count(array_intersect(
            $recipeIngredientKeys,
            $requiredIngredientKeys,
        ));

        return $matchingMode === self::MATCH_ANY
            ? $matchingRequiredIngredientCount > 0
            : $matchingRequiredIngredientCount === count($requiredIngredientKeys);
    }

    private function buildIngredientKey(string $ingredientName): string
    {
        return $this->ingredientClassifier->normalizeForComparison(
            $this->ingredientClassifier->resolveCanonicalName($ingredientName),
        );
    }
}
