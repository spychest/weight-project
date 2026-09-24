<?php

namespace App\Tests\Unit\Entity;

use App\Entity\IngredientCatalogItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IngredientCatalogItemTest extends TestCase
{
    #[Test]
    public function itCleansAndDeduplicatesAliases(): void
    {
        $catalogItem = (new IngredientCatalogItem())->setAliases([
            ' tomate concassé ',
            '',
            'tomate concassé',
            'tomates concassées',
        ]);

        self::assertSame(
            ['tomate concassé', 'tomates concassées'],
            $catalogItem->getAliases(),
        );
    }
}
