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
        ]);

        self::assertSame(
            $expectedCategory,
            (new IngredientClassifier($catalogProvider))->classify($ingredient),
        );
    }
}
