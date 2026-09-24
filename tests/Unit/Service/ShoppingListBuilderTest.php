<?php

namespace App\Tests\Unit\Service;

use App\Entity\ShoppingList;
use App\Entity\ShoppingListRecipe;
use App\Service\Shopping\IngredientClassifier;
use App\Service\Shopping\ShoppingListBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShoppingListBuilderTest extends TestCase
{
    #[Test]
    public function itAggregatesSameIngredientsAndConvertsCompatibleUnits(): void
    {
        $list = new ShoppingList();
        $list->addRecipeSelection((new ShoppingListRecipe())->setPreparationCount(2)->setIngredientSnapshot([
            ['name' => 'Poulet', 'quantity' => 500, 'unit' => 'g'],
        ]));
        $list->addRecipeSelection((new ShoppingListRecipe())->setIngredientSnapshot([
            ['name' => 'poulet', 'quantity' => 1, 'unit' => 'kg'],
        ]));

        $this->createShoppingListBuilder()->rebuild($list);

        self::assertCount(1, $list->getItems());
        self::assertSame(2000.0, $list->getItems()[0]['quantity']);
        self::assertSame('g', $list->getItems()[0]['unit']);
        self::assertSame('Viandes et poissons', $list->getItems()[0]['category']);
    }

    #[Test]
    public function itDoesNotMergeIncompatibleUnits(): void
    {
        $list = new ShoppingList();
        $list->addRecipeSelection((new ShoppingListRecipe())->setIngredientSnapshot([
            ['name' => 'Citron', 'quantity' => 1, 'unit' => 'pièce'],
            ['name' => 'Citron', 'quantity' => 20, 'unit' => 'ml'],
        ]));
        $this->createShoppingListBuilder()->rebuild($list);
        self::assertCount(2, $list->getItems());
    }

    #[Test]
    public function anExcludedGeneratedItemDoesNotReturnWhenTheListIsRebuilt(): void
    {
        $list = new ShoppingList();
        $list->addRecipeSelection((new ShoppingListRecipe())->setIngredientSnapshot([
            ['name' => 'Poulet', 'quantity' => 500, 'unit' => 'g'],
        ]));
        $builder = $this->createShoppingListBuilder();
        $builder->rebuild($list);
        $list->excludeGeneratedItem($list->getItems()[0]['key']);
        $builder->rebuild($list);
        self::assertSame([], $list->getItems());
    }

    private function createShoppingListBuilder(): ShoppingListBuilder
    {
        $catalogProvider = new InMemoryIngredientCatalogProvider([
            ['canonicalName' => 'poulet', 'category' => 'Viandes et poissons', 'aliases' => []],
            ['canonicalName' => 'citron', 'category' => 'Fruits et légumes', 'aliases' => []],
        ]);

        return new ShoppingListBuilder(new IngredientClassifier($catalogProvider));
    }
}
