<?php

namespace App\Tests\Unit\Service;

use App\Service\Shopping\IngredientClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IngredientClassifierTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function ingredients(): iterable
    {
        yield 'chicken stays in meat' => ['Poulet', 'Viandes et poissons'];
        yield 'cumin stays in spices' => ['Cumin moulu', 'Épices et condiments'];
        yield 'paprika stays in spices' => ['Paprika fumé', 'Épices et condiments'];
        yield 'coconut milk is Asian grocery' => ['Lait de coco', 'Épicerie asiatique'];
        yield 'curry paste is Asian grocery' => ['Pâte de curry rouge', 'Épicerie asiatique'];
        yield 'chipotle is Latin grocery' => ['Piment chipotle en poudre', 'Épices et condiments'];
        yield 'plural garlic cloves with curly apostrophe are recognized' => ['Gousses d’ail fraîches', 'Fruits et légumes'];
        yield 'accentless plural tomatoes are recognized' => ['Tomates concassees en boîte', 'Conserves et bocaux'];
        yield 'a longer unrelated word does not match a substring' => ['Laitue', 'Fruits et légumes'];
        yield 'unknown falls back safely' => ['Produit mystérieux', 'Autres'];
    }

    #[Test]
    #[DataProvider('ingredients')]
    public function itClassifiesIngredientsWithoutExternalService(string $ingredient, string $expectedCategory): void
    {
        $catalogProvider = new InMemoryIngredientCatalogProvider([
            ['canonicalName' => 'poulet', 'category' => 'Viandes et poissons', 'aliases' => []],
            ['canonicalName' => 'cumin', 'category' => 'Épices et condiments', 'aliases' => ['cumin moulu']],
            ['canonicalName' => 'paprika', 'category' => 'Épices et condiments', 'aliases' => ['paprika fumé']],
            ['canonicalName' => 'lait', 'category' => 'Produits frais', 'aliases' => []],
            ['canonicalName' => 'lait de coco', 'category' => 'Épicerie asiatique', 'aliases' => []],
            ['canonicalName' => 'pâtes', 'category' => 'Féculents et légumineuses', 'aliases' => ['pâte']],
            ['canonicalName' => 'pâte de curry', 'category' => 'Épicerie asiatique', 'aliases' => []],
            ['canonicalName' => 'piment', 'category' => 'Épices et condiments', 'aliases' => ['piment chipotle']],
            ['canonicalName' => 'ail', 'category' => 'Fruits et légumes', 'aliases' => ["gousse d'ail"]],
            ['canonicalName' => 'tomate concassée', 'category' => 'Conserves et bocaux', 'aliases' => ['tomates concassées']],
            ['canonicalName' => 'laitue', 'category' => 'Fruits et légumes', 'aliases' => []],
        ]);

        self::assertSame(
            $expectedCategory,
            (new IngredientClassifier($catalogProvider))->classify($ingredient),
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function normalizedNames(): iterable
    {
        yield 'case accents and punctuation' => ['  PÂTES, fraîches. ', 'pate fraiche'];
        yield 'curly apostrophe and packaging' => ['Gousses d’ail fraîches', 'ail fraiche'];
        yield 'parenthetical precision' => ['Bœuf haché (5 % MG)', 'boeuf hache'];
        yield 'conservation state is preserved' => ['Haricots rouges en conserve', 'haricot rouge en conserve'];
        yield 'frozen state is preserved' => ['Petits pois surgelés', 'petit pois surgele'];
        yield 'singularization exception' => ['Noix', 'noix'];
    }

    #[Test]
    #[DataProvider('normalizedNames')]
    public function itNormalizesIngredientNamesForReliableComparison(
        string $ingredientName,
        string $expectedNormalizedName,
    ): void {
        $classifier = new IngredientClassifier(new InMemoryIngredientCatalogProvider([]));

        self::assertSame(
            $expectedNormalizedName,
            $classifier->normalizeForComparison($ingredientName),
        );
    }

    #[Test]
    public function everyCatalogNameAndAliasKeepsItsExpectedCategory(): void
    {
        $catalogContents = file_get_contents(dirname(__DIR__, 3).'/resources/ingredient_catalog.json');
        self::assertNotFalse($catalogContents);
        $catalog = json_decode($catalogContents, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($catalog);
        self::assertIsArray($catalog['ingredients']);

        $classifier = new IngredientClassifier(
            new InMemoryIngredientCatalogProvider($catalog['ingredients']),
        );

        foreach ($catalog['ingredients'] as $catalogEntry) {
            foreach ([$catalogEntry['canonicalName'], ...$catalogEntry['aliases']] as $knownIngredientName) {
                self::assertSame(
                    $catalogEntry['category'],
                    $classifier->classify($knownIngredientName),
                    sprintf('Classement incorrect pour « %s ».', $knownIngredientName),
                );
            }
        }
    }
}
