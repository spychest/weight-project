<?php

namespace App\Service\Shopping;

final class IngredientSuggestionProvider
{
    public function __construct(
        private readonly IngredientCatalogProviderInterface $ingredientCatalogProvider,
        private readonly IngredientClassifier $ingredientClassifier,
    ) {
    }

    /**
     * @return list<array{name: string, category: string}>
     */
    public function findSuggestions(string $searchTerm, int $maximumSuggestionCount = 10): array
    {
        $normalizedSearchTerm = $this->ingredientClassifier->normalizeForComparison($searchTerm);
        if (mb_strlen($normalizedSearchTerm) < 2 || $maximumSuggestionCount < 1) {
            return [];
        }

        $rankedSuggestions = [];
        foreach ($this->ingredientCatalogProvider->getCatalogEntries() as $catalogEntry) {
            $bestMatchingScore = null;
            foreach ([$catalogEntry['canonicalName'], ...$catalogEntry['aliases']] as $searchableName) {
                $matchingScore = $this->calculateMatchingScore(
                    $this->ingredientClassifier->normalizeForComparison($searchableName),
                    $normalizedSearchTerm,
                );

                if ($matchingScore !== null && ($bestMatchingScore === null || $matchingScore < $bestMatchingScore)) {
                    $bestMatchingScore = $matchingScore;
                }
            }

            if ($bestMatchingScore !== null) {
                $rankedSuggestions[] = [
                    'name' => $catalogEntry['canonicalName'],
                    'category' => $catalogEntry['category'],
                    'score' => $bestMatchingScore,
                ];
            }
        }

        usort($rankedSuggestions, static function (array $firstSuggestion, array $secondSuggestion): int {
            return [$firstSuggestion['score'], $firstSuggestion['name']]
                <=> [$secondSuggestion['score'], $secondSuggestion['name']];
        });

        return array_map(
            static fn (array $suggestion): array => [
                'name' => $suggestion['name'],
                'category' => $suggestion['category'],
            ],
            array_slice($rankedSuggestions, 0, $maximumSuggestionCount),
        );
    }

    private function calculateMatchingScore(string $searchableName, string $searchTerm): ?int
    {
        if ($searchableName === $searchTerm) {
            return 0;
        }

        if (str_starts_with($searchableName, $searchTerm)) {
            return 100 + mb_strlen($searchableName);
        }

        if (str_contains(' '.$searchableName, ' '.$searchTerm)) {
            return 200 + mb_strlen($searchableName);
        }

        if (str_contains($searchableName, $searchTerm)) {
            return 300 + mb_strlen($searchableName);
        }

        return null;
    }
}
