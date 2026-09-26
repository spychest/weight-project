<?php

namespace App\Service\Shopping;

use App\Entity\ShoppingList;

final class ShoppingListBuilder
{
    public function __construct(
        private readonly IngredientClassifier $ingredientClassifier,
        private readonly IngredientUnitNormalizer $ingredientUnitNormalizer,
    ) {
    }

    public function rebuild(ShoppingList $shoppingList): void
    {
        $previousItemsByKey = [];
        $manualItems = [];
        foreach ($shoppingList->getItems() as $item) {
            if ($item['manual']) {
                $manualItems[] = $item;
            } else {
                $previousItemsByKey[$item['key']] = $item;
            }
        }

        $generatedItemsByKey = [];
        $legacyKeysByGeneratedKey = [];
        foreach ($shoppingList->getRecipeSelections() as $recipeSelection) {
            foreach ($recipeSelection->getIngredientSnapshot() as $ingredient) {
                $name = trim($ingredient['name']);
                $quantity = (float) str_replace(',', '.', (string) $ingredient['quantity']);
                if ($name === '' || $quantity <= 0) {
                    continue;
                }
                $normalizedUnit = $this->ingredientUnitNormalizer->normalize($ingredient['unit']);
                $unit = $normalizedUnit['unit'];
                $canonicalName = $this->ingredientClassifier->resolveCanonicalName($name);
                $normalizedName = $this->ingredientClassifier->normalizeForComparison($canonicalName);
                $key = hash('sha256', $normalizedName.'|'.$unit);
                $legacyKey = $this->buildLegacyItemKey($name, $ingredient['unit']);
                $calculatedQuantity = $quantity
                    * $normalizedUnit['factor']
                    * $recipeSelection->getPreparationCount();
                $legacyKeysByGeneratedKey[$key][$legacyKey] = true;

                if (!isset($generatedItemsByKey[$key])) {
                    $generatedItemsByKey[$key] = [
                        'key' => $key,
                        'name' => $canonicalName,
                        'quantity' => 0.0,
                        'calculatedQuantity' => 0.0,
                        'unit' => $unit,
                        'category' => $this->ingredientClassifier->classify($canonicalName),
                        'categoryOverridden' => false,
                        'checked' => false,
                        'manual' => false,
                    ];
                }
                $generatedItemsByKey[$key]['quantity'] += $calculatedQuantity;
                $generatedItemsByKey[$key]['calculatedQuantity'] += $calculatedQuantity;
            }
        }

        foreach ($generatedItemsByKey as $key => &$generatedItem) {
            $previousItem = $previousItemsByKey[$key] ?? $this->findPreviousItemByLegacyKey(
                $previousItemsByKey,
                array_keys($legacyKeysByGeneratedKey[$key] ?? []),
            );
            if ($previousItem === null) {
                continue;
            }
            $generatedItem['checked'] = (bool) $previousItem['checked'];
            $generatedItem['name'] = $previousItem['name'];
            $categoryWasOverridden = $previousItem['categoryOverridden'] ?? false;
            if ($categoryWasOverridden) {
                $generatedItem['category'] = $previousItem['category'];
                $generatedItem['categoryOverridden'] = true;
            }
            $previousCalculatedQuantity = $previousItem['calculatedQuantity'];
            if (abs((float) $previousItem['quantity'] - $previousCalculatedQuantity) > 0.0001) {
                $generatedItem['quantity'] = (float) $previousItem['quantity'];
            }
        }
        unset($generatedItem);

        $excludedGeneratedItemKeys = $shoppingList->getExcludedGeneratedItemKeys();
        foreach (array_keys($generatedItemsByKey) as $generatedItemKey) {
            $compatibleKeys = [$generatedItemKey, ...array_keys($legacyKeysByGeneratedKey[$generatedItemKey] ?? [])];
            if (array_intersect($compatibleKeys, $excludedGeneratedItemKeys) !== []) {
                unset($generatedItemsByKey[$generatedItemKey]);
            }
        }

        $allItems = array_merge(array_values($generatedItemsByKey), $manualItems);
        usort(
            $allItems,
            static fn (array $left, array $right): int => [
                $left['category'],
                $left['name'],
            ] <=> [
                $right['category'],
                $right['name'],
            ],
        );
        $shoppingList->setItems($allItems)->touch();
    }

    private function buildLegacyItemKey(string $ingredientName, string $unit): string
    {
        $legacyUnitConversions = [
            'kg' => 'g',
            'g' => 'g',
            'l' => 'ml',
            'cl' => 'ml',
            'ml' => 'ml',
            'pièce' => 'pièce',
            'pièces' => 'pièce',
            'piece' => 'pièce',
            'pieces' => 'pièce',
        ];
        $normalizedLegacyUnit = mb_strtolower(trim($unit));
        $normalizedLegacyUnit = $legacyUnitConversions[$normalizedLegacyUnit] ?? $normalizedLegacyUnit;

        return hash(
            'sha256',
            $this->ingredientClassifier->normalizeName($ingredientName).'|'.$normalizedLegacyUnit,
        );
    }

    /**
     * @param array<string, array<string, mixed>> $previousItemsByKey
     * @param list<string>                        $legacyKeys
     *
     * @return array<string, mixed>|null
     */
    private function findPreviousItemByLegacyKey(array $previousItemsByKey, array $legacyKeys): ?array
    {
        foreach ($legacyKeys as $legacyKey) {
            if (isset($previousItemsByKey[$legacyKey])) {
                return $previousItemsByKey[$legacyKey];
            }
        }

        return null;
    }
}
