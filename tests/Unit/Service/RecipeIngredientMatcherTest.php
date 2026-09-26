<?php

namespace App\Tests\Unit\Service;

use App\Entity\Recipe;
use App\Service\Recipe\RecipeIngredientMatcher;
use App\Service\Shopping\IngredientClassifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RecipeIngredientMatcherTest extends TestCase
{
    private RecipeIngredientMatcher $matcher;

    protected function setUp(): void
    {
        $catalogProvider = new InMemoryIngredientCatalogProvider([
            ['canonicalName' => 'poulet', 'category' => 'Viandes et poissons', 'aliases' => ['blanc de poulet']],
            ['canonicalName' => 'lait', 'category' => 'Produits frais', 'aliases' => []],
            ['canonicalName' => 'lait de coco', 'category' => 'Épicerie asiatique', 'aliases' => ['crème de coco']],
            ['canonicalName' => 'coriandre', 'category' => 'Fruits et légumes', 'aliases' => []],
        ]);
        $this->matcher = new RecipeIngredientMatcher(new IngredientClassifier($catalogProvider));
    }

    #[Test]
    public function itMatchesAllRequiredIngredientsUsingCatalogAliases(): void
    {
        $recipe = $this->createRecipeWithIngredients(['Blancs de poulet fermier', 'Crème de coco']);

        self::assertTrue($this->matcher->matches($recipe, ['poulet', 'lait de coco'], []));
        self::assertFalse($this->matcher->matches($recipe, ['poulet', 'coriandre'], []));
    }

    #[Test]
    public function itCanMatchAtLeastOneRequiredIngredient(): void
    {
        $recipe = $this->createRecipeWithIngredients(['Poulet']);

        self::assertTrue($this->matcher->matches(
            $recipe,
            ['poulet', 'coriandre'],
            [],
            RecipeIngredientMatcher::MATCH_ANY,
        ));
    }

    #[Test]
    public function anExcludedIngredientAlwaysRejectsTheRecipe(): void
    {
        $recipe = $this->createRecipeWithIngredients(['Poulet', 'Coriandre fraîche']);

        self::assertFalse($this->matcher->matches(
            $recipe,
            ['poulet'],
            ['coriandre'],
            RecipeIngredientMatcher::MATCH_ANY,
        ));
    }

    #[Test]
    public function coconutMilkDoesNotMatchRegularMilk(): void
    {
        $recipe = $this->createRecipeWithIngredients(['Lait de coco']);

        self::assertFalse($this->matcher->matches($recipe, ['lait'], []));
        self::assertTrue($this->matcher->matches($recipe, ['lait de coco'], []));
    }

    /** @param list<string> $ingredientNames */
    private function createRecipeWithIngredients(array $ingredientNames): Recipe
    {
        return (new Recipe())->setIngredients(array_map(
            static fn (string $ingredientName): array => [
                'name' => $ingredientName,
                'quantity' => 1,
                'unit' => 'pièce',
            ],
            $ingredientNames,
        ));
    }
}
