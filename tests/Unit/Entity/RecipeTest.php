<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Recipe;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RecipeTest extends TestCase
{
    #[Test]
    public function itStartsWithModularRequiredRowsAndEmptyOptionalLists(): void
    {
        $recipe = new Recipe();

        self::assertSame([], $recipe->getUtensils());
        self::assertSame(1, $recipe->getServings());
        self::assertSame([], $recipe->getSetupSteps());
        self::assertSame([], $recipe->getTips());
        self::assertCount(1, $recipe->getIngredients());
        self::assertSame([''], $recipe->getPreparationSteps());
    }

    #[Test]
    public function itNormalizesEmptyTextListItems(): void
    {
        $recipe = (new Recipe())
            ->setUtensils([' Casserole ', '', '  Fouet'])
            ->setSetupSteps([' Couper les légumes ', '   '])
            ->setPreparationSteps([' Mélanger ', '', 'Cuire'])
            ->setTips([' Préparer la veille ', '   ']);

        self::assertSame(['Casserole', 'Fouet'], $recipe->getUtensils());
        self::assertSame(['Couper les légumes'], $recipe->getSetupSteps());
        self::assertSame(['Mélanger', 'Cuire'], $recipe->getPreparationSteps());
        self::assertSame(['Préparer la veille'], $recipe->getTips());
    }
}
