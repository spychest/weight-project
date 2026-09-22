<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\DrinkType;
use App\Enum\MealType;
use PHPUnit\Framework\TestCase;

final class TrackingTypeLabelsTest extends TestCase
{
    public function testMealTypesHaveExplicitFrenchLabels(): void
    {
        self::assertSame('Petit déjeuner', MealType::BREAKFAST->label());
        self::assertSame('Déjeuner', MealType::LUNCH->label());
        self::assertSame('Dîner', MealType::DINNER->label());
        self::assertSame('Snack', MealType::SNACK->label());
    }

    public function testDrinkTypesHaveExplicitFrenchLabels(): void
    {
        self::assertSame('Eau', DrinkType::WATER->label());
        self::assertSame('Soda', DrinkType::SODA->label());
        self::assertSame('Soda sans sucre', DrinkType::SUGAR_FREE_SODA->label());
        self::assertSame('Jus', DrinkType::JUICE->label());
        self::assertSame('Café', DrinkType::COFFEE->label());
        self::assertSame('Thé', DrinkType::TEA->label());
        self::assertSame('Autre', DrinkType::OTHER->label());
    }

    public function testSugarFreeSodaKeepsAStableTechnicalValue(): void
    {
        self::assertSame('soda_sans_sucre', DrinkType::SUGAR_FREE_SODA->value);
    }
}
