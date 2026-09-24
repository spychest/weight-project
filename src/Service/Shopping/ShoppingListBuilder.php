<?php

namespace App\Service\Shopping;

use App\Entity\ShoppingList;

final class ShoppingListBuilder
{
    /** @var array<string, array{unit: string, factor: float}> */
    private const UNIT_CONVERSIONS = [
        'kg' => ['unit' => 'g', 'factor' => 1000.0],
        'g' => ['unit' => 'g', 'factor' => 1.0],
        'l' => ['unit' => 'ml', 'factor' => 1000.0],
        'cl' => ['unit' => 'ml', 'factor' => 10.0],
        'ml' => ['unit' => 'ml', 'factor' => 1.0],
        'pièce' => ['unit' => 'pièce', 'factor' => 1.0],
        'pièces' => ['unit' => 'pièce', 'factor' => 1.0],
        'piece' => ['unit' => 'pièce', 'factor' => 1.0],
        'pieces' => ['unit' => 'pièce', 'factor' => 1.0],
    ];

    public function __construct(
        private readonly IngredientClassifier $ingredientClassifier,
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
        foreach ($shoppingList->getRecipeSelections() as $recipeSelection) {
            foreach ($recipeSelection->getIngredientSnapshot() as $ingredient) {
                $name = trim($ingredient['name']);
                $quantity = (float) str_replace(',', '.', (string) $ingredient['quantity']);
                if ($name === '' || $quantity <= 0) {
                    continue;
                }
                [$unit, $conversionFactor] = $this->normalizeUnit($ingredient['unit']);
                $normalizedName = $this->ingredientClassifier->normalizeName($name);
                $key = hash('sha256', $normalizedName.'|'.$unit);
                $calculatedQuantity = $quantity * $conversionFactor * $recipeSelection->getPreparationCount();

                if (!isset($generatedItemsByKey[$key])) {
                    $generatedItemsByKey[$key] = [
                        'key' => $key,
                        'name' => $name,
                        'quantity' => 0.0,
                        'calculatedQuantity' => 0.0,
                        'unit' => $unit,
                        'category' => $this->ingredientClassifier->classify($name),
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
            $previousItem = $previousItemsByKey[$key] ?? null;
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

        foreach ($shoppingList->getExcludedGeneratedItemKeys() as $excludedKey) {
            unset($generatedItemsByKey[$excludedKey]);
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

    public function reclassifyGeneratedItems(ShoppingList $shoppingList): void
    {
        $items = $shoppingList->getItems();
        foreach ($items as &$item) {
            $categoryWasOverridden = $item['categoryOverridden'] ?? false;
            if ($item['manual'] || $categoryWasOverridden) {
                continue;
            }

            $item['category'] = $this->ingredientClassifier->classify($item['name']);
            $item['categoryOverridden'] = false;
        }
        unset($item);

        $shoppingList->setItems($items)->touch();
    }

    /** @return array{string, float} */
    private function normalizeUnit(string $unit): array
    {
        $normalizedUnit = mb_strtolower(trim($unit));
        $conversion = self::UNIT_CONVERSIONS[$normalizedUnit] ?? null;

        return $conversion === null ? [$normalizedUnit, 1.0] : [$conversion['unit'], $conversion['factor']];
    }
}
