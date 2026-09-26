<?php

namespace App\Tests\Unit\Service;

use App\Entity\ShoppingList;
use App\Entity\ShoppingListRecipe;
use App\Service\Shopping\IngredientClassifier;
use App\Service\Shopping\IngredientUnitNormalizer;
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
    public function itAggregatesCatalogAliasesAndPluralNames(): void
    {
        $list = new ShoppingList();
        $list->addRecipeSelection((new ShoppingListRecipe())->setIngredientSnapshot([
            ['name' => 'Blanc de poulet', 'quantity' => 400, 'unit' => 'grammes'],
            ['name' => 'poulets', 'quantity' => 0.5, 'unit' => 'kilogramme'],
        ]));

        $this->createShoppingListBuilder()->rebuild($list);

        self::assertCount(1, $list->getItems());
        self::assertSame('Poulet', $list->getItems()[0]['name']);
        self::assertSame(900.0, $list->getItems()[0]['quantity']);
        self::assertSame('g', $list->getItems()[0]['unit']);
    }

    #[Test]
    public function itConvertsCookingSpoonsToMillilitres(): void
    {
        $list = new ShoppingList();
        $list->addRecipeSelection((new ShoppingListRecipe())->setIngredientSnapshot([
            ['name' => 'Huile', 'quantity' => 1, 'unit' => 'cuillère à soupe'],
            ['name' => 'Huile', 'quantity' => 2, 'unit' => 'c. à café'],
        ]));

        $this->createShoppingListBuilder()->rebuild($list);

        self::assertCount(1, $list->getItems());
        self::assertSame(25.0, $list->getItems()[0]['quantity']);
        self::assertSame('ml', $list->getItems()[0]['unit']);
    }

    #[Test]
    public function itKeepsContainersSeparateFromMeasuredQuantities(): void
    {
        $list = new ShoppingList();
        $list->addRecipeSelection((new ShoppingListRecipe())->setIngredientSnapshot([
            ['name' => 'Tomate', 'quantity' => 2, 'unit' => 'boîtes'],
            ['name' => 'Tomates', 'quantity' => 500, 'unit' => 'g'],
        ]));

        $this->createShoppingListBuilder()->rebuild($list);

        self::assertCount(2, $list->getItems());
        self::assertSame(['boîte', 'g'], array_column($list->getItems(), 'unit'));
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

    #[Test]
    public function aPreviouslyExcludedAliasStaysExcludedAfterCanonicalization(): void
    {
        $list = new ShoppingList();
        $list->addRecipeSelection((new ShoppingListRecipe())->setIngredientSnapshot([
            ['name' => 'Blanc de poulet', 'quantity' => 500, 'unit' => 'g'],
        ]));
        $legacyKey = hash('sha256', 'blanc de poulet|g');
        $list->excludeGeneratedItem($legacyKey);

        $this->createShoppingListBuilder()->rebuild($list);

        self::assertSame([], $list->getItems());
    }

    #[Test]
    public function aManualQuantityCorrectionSurvivesCanonicalization(): void
    {
        $list = new ShoppingList();
        $list->addRecipeSelection((new ShoppingListRecipe())->setIngredientSnapshot([
            ['name' => 'Blanc de poulet', 'quantity' => 500, 'unit' => 'g'],
        ]));
        $list->setItems([[
            'key' => hash('sha256', 'blanc de poulet|g'),
            'name' => 'Blanc de poulet fermier',
            'quantity' => 750.0,
            'calculatedQuantity' => 500.0,
            'unit' => 'g',
            'category' => 'Viandes et poissons',
            'categoryOverridden' => false,
            'checked' => true,
            'manual' => false,
        ]]);

        $this->createShoppingListBuilder()->rebuild($list);

        self::assertCount(1, $list->getItems());
        self::assertSame(750.0, $list->getItems()[0]['quantity']);
        self::assertSame('Blanc de poulet fermier', $list->getItems()[0]['name']);
        self::assertTrue($list->getItems()[0]['checked']);
    }

    private function createShoppingListBuilder(): ShoppingListBuilder
    {
        $catalogProvider = new InMemoryIngredientCatalogProvider([
            ['canonicalName' => 'Poulet', 'category' => 'Viandes et poissons', 'aliases' => ['Blanc de poulet']],
            ['canonicalName' => 'Citron', 'category' => 'Fruits et légumes', 'aliases' => []],
            ['canonicalName' => 'Huile', 'category' => 'Huiles et sauces', 'aliases' => []],
            ['canonicalName' => 'Tomate', 'category' => 'Fruits et légumes', 'aliases' => ['Tomates']],
        ]);

        return new ShoppingListBuilder(
            new IngredientClassifier($catalogProvider),
            new IngredientUnitNormalizer(),
        );
    }
}
