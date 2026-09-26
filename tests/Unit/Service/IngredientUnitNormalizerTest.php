<?php

namespace App\Tests\Unit\Service;

use App\Service\Shopping\IngredientUnitNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IngredientUnitNormalizerTest extends TestCase
{
    /** @return iterable<string, array{string, string, float}> */
    public static function compatibleUnits(): iterable
    {
        yield 'kilograms' => ['Kilogrammes', 'g', 1000.0];
        yield 'grams' => ['gr', 'g', 1.0];
        yield 'litres' => ['litres', 'ml', 1000.0];
        yield 'decilitres' => ['dl', 'ml', 100.0];
        yield 'tablespoons' => ['cuillères à soupe', 'ml', 15.0];
        yield 'teaspoon abbreviation' => ['c. à c.', 'ml', 5.0];
        yield 'accentless pieces' => ['pieces', 'pièce', 1.0];
        yield 'boxes' => ['boîtes', 'boîte', 1.0];
    }

    #[Test]
    #[DataProvider('compatibleUnits')]
    public function itNormalizesKnownUnits(string $unit, string $expectedUnit, float $expectedFactor): void
    {
        self::assertSame(
            ['unit' => $expectedUnit, 'factor' => $expectedFactor],
            (new IngredientUnitNormalizer())->normalize($unit),
        );
    }

    #[Test]
    public function itPreservesUnknownUnitsWithoutInventingAConversion(): void
    {
        self::assertSame(
            ['unit' => 'barquette', 'factor' => 1.0],
            (new IngredientUnitNormalizer())->normalize('Barquette'),
        );
    }
}
