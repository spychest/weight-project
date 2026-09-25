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
        self::assertSame('./ingredient_catalog.schema.json', $catalog['$schema']);
        self::assertSame(1, $catalog['schemaVersion']);
        self::assertArrayHasKey('ingredients', $catalog);
        self::assertGreaterThanOrEqual(90, count($catalog['ingredients']));

        $normalizedNames = [];
        foreach ($catalog['ingredients'] as $ingredient) {
            self::assertSame(['aliases', 'canonicalName', 'category'], $this->sortedKeys($ingredient));
            self::assertIsString($ingredient['canonicalName']);
            self::assertNotSame('', trim($ingredient['canonicalName']));
            self::assertIsString($ingredient['category']);
            self::assertNotSame('', trim($ingredient['category']));
            self::assertIsArray($ingredient['aliases']);
            self::assertTrue(array_is_list($ingredient['aliases']));
            self::assertSame($ingredient['aliases'], array_values(array_unique($ingredient['aliases'])));
            foreach ($ingredient['aliases'] as $alias) {
                self::assertIsString($alias);
                self::assertNotSame('', trim($alias));
            }
            $normalizedNames[] = mb_strtolower(trim($ingredient['canonicalName']));
        }

        self::assertSame($normalizedNames, array_values(array_unique($normalizedNames)));
    }

    #[Test]
    public function catalogSchemaIsAValidVersionedJsonSchema(): void
    {
        $schemaPath = dirname(__DIR__, 3).'/resources/ingredient_catalog.schema.json';
        $schemaContents = file_get_contents($schemaPath);

        self::assertNotFalse($schemaContents);
        $schema = json_decode($schemaContents, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($schema);
        self::assertSame('https://json-schema.org/draft/2020-12/schema', $schema['$schema']);
        self::assertSame(1, $schema['properties']['schemaVersion']['const']);
        self::assertFalse($schema['additionalProperties']);
        self::assertFalse($schema['$defs']['ingredient']['additionalProperties']);
    }

    /** @param array<string, mixed> $data */
    private function sortedKeys(array $data): array
    {
        $keys = array_keys($data);
        sort($keys);

        return $keys;
    }
}
