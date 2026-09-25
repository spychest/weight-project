<?php

namespace App\Tests\Unit\Service;

use App\Service\Shopping\IngredientClassifier;
use App\Service\Shopping\IngredientSuggestionProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IngredientSuggestionProviderTest extends TestCase
{
    private IngredientSuggestionProvider $suggestionProvider;

    protected function setUp(): void
    {
        $catalogProvider = new InMemoryIngredientCatalogProvider([
            ['canonicalName' => 'Tomate', 'category' => 'Fruits et légumes', 'aliases' => ['tomates']],
            ['canonicalName' => 'Tomate concassée', 'category' => 'Conserves', 'aliases' => ['tomates concassées']],
            ['canonicalName' => 'Concentré de tomate', 'category' => 'Conserves', 'aliases' => []],
            ['canonicalName' => 'Lait de coco', 'category' => 'Épicerie asiatique', 'aliases' => ['crème de coco']],
        ]);
        $classifier = new IngredientClassifier($catalogProvider);
        $this->suggestionProvider = new IngredientSuggestionProvider($catalogProvider, $classifier);
    }

    #[Test]
    public function itSuggestsCanonicalNamesFromNormalizedNamesAndAliases(): void
    {
        self::assertSame(
            [
                ['name' => 'Lait de coco', 'category' => 'Épicerie asiatique'],
            ],
            $this->suggestionProvider->findSuggestions('creme'),
        );
    }

    #[Test]
    public function itPrioritizesNamesStartingWithTheSearchTerm(): void
    {
        self::assertSame(
            ['Tomate', 'Tomate concassée', 'Concentré de tomate'],
            array_column($this->suggestionProvider->findSuggestions('tom'), 'name'),
        );
    }

    #[Test]
    public function itDoesNotSuggestAnythingBeforeTwoCharacters(): void
    {
        self::assertSame([], $this->suggestionProvider->findSuggestions('t'));
    }

    #[Test]
    public function itHonorsTheMaximumSuggestionCount(): void
    {
        self::assertCount(2, $this->suggestionProvider->findSuggestions('tom', 2));
    }
}
