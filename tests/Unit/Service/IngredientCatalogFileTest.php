<?php

namespace App\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IngredientCatalogFileTest extends TestCase
{
    #[Test]
    public function catalogContainsValidAndUniquelyNamedIngredients(): void
    {
        $catalogPath = dirname(__DIR__, 3).'/resources/ingredient_catalog.json';
        $catalogContents = file_get_contents($catalogPath);

        self::assertNotFalse($catalogContents);
        $catalog = json_decode($catalogContents, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($catalog);
        self::assertArrayHasKey('ingredients', $catalog);
        self::assertGreaterThanOrEqual(90, count($catalog['ingredients']));

        $normalizedNames = [];
        foreach ($catalog['ingredients'] as $ingredient) {
            self::assertIsString($ingredient['canonicalName']);
            self::assertNotSame('', trim($ingredient['canonicalName']));
            self::assertIsString($ingredient['category']);
            self::assertNotSame('', trim($ingredient['category']));
            self::assertIsArray($ingredient['aliases']);
            $normalizedNames[] = mb_strtolower(trim($ingredient['canonicalName']));
        }

        self::assertSame($normalizedNames, array_values(array_unique($normalizedNames)));
    }
}
