<?php

namespace App\Service\Shopping;

use function Symfony\Component\String\u;

final class IngredientClassifier
{
    /** @var list<string> */
    private const LEADING_ARTICLES = ['un', 'une', 'des', 'du', 'de', 'la', 'le', 'les', 'l', 'd'];

    /** @var list<string> */
    private const CONTAINER_WORDS = [
        'boite',
        'boites',
        'bocal',
        'bocaux',
        'gousse',
        'gousses',
        'paquet',
        'paquets',
        'pincee',
        'pincees',
        'sachet',
        'sachets',
        'tranche',
        'tranches',
    ];

    /** @var list<string> */
    private const TRAILING_QUALIFIERS = [
        'bio',
    ];

    /** @var list<string> */
    private const SINGULARIZATION_EXCEPTIONS = [
        'ananas',
        'anis',
        'brebis',
        'cassis',
        'couscous',
        'houmous',
        'jus',
        'mais',
        'noix',
        'pois',
        'radis',
        'riz',
    ];

    public function __construct(
        private readonly IngredientCatalogProviderInterface $ingredientCatalogProvider,
    ) {
    }

    public function normalizeName(string $ingredientName): string
    {
        $normalizedName = mb_strtolower(trim($ingredientName));
        $normalizedName = preg_replace('/\s+/u', ' ', $normalizedName) ?? $normalizedName;

        return trim($normalizedName, " \t\n\r\0\x0B.,;:");
    }

    public function normalizeForComparison(string $ingredientName): string
    {
        $normalizedName = preg_replace('/\([^)]*\)/u', ' ', $ingredientName) ?? $ingredientName;
        $normalizedName = str_replace(['’', '`', '´'], "'", $normalizedName);
        $normalizedName = u($this->normalizeName($normalizedName))->ascii()->toString();
        $normalizedName = str_replace(['&', "'"], [' et ', ' '], $normalizedName);
        $normalizedName = preg_replace('/[^a-z0-9]+/', ' ', $normalizedName) ?? $normalizedName;
        $tokens = preg_split('/\s+/', trim($normalizedName), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        while ($tokens !== [] && ctype_digit($tokens[0])) {
            array_shift($tokens);
        }
        $tokens = $this->removeLeadingWords($tokens, self::LEADING_ARTICLES);

        if ($tokens !== [] && in_array($tokens[0], self::CONTAINER_WORDS, true)) {
            array_shift($tokens);
            $tokens = $this->removeLeadingWords($tokens, self::LEADING_ARTICLES);
        }

        while ($tokens !== [] && in_array($tokens[array_key_last($tokens)], self::TRAILING_QUALIFIERS, true)) {
            array_pop($tokens);
        }

        return implode(' ', array_map($this->singularizeToken(...), $tokens));
    }

    public function classify(string $ingredientName): string
    {
        $normalizedIngredientName = $this->normalizeForComparison($ingredientName);
        $bestCategory = 'Autres';
        $longestMatchingTermLength = 0;

        foreach ($this->ingredientCatalogProvider->getCatalogEntries() as $catalogEntry) {
            $terms = [$catalogEntry['canonicalName'], ...$catalogEntry['aliases']];
            foreach ($terms as $term) {
                $normalizedTerm = $this->normalizeForComparison($term);
                $termLength = mb_strlen($normalizedTerm);

                if (
                    $normalizedTerm !== ''
                    && $termLength > $longestMatchingTermLength
                    && ($normalizedIngredientName === $normalizedTerm
                        || str_contains(' '.$normalizedIngredientName.' ', ' '.$normalizedTerm.' '))
                ) {
                    $bestCategory = $catalogEntry['category'];
                    $longestMatchingTermLength = $termLength;
                }
            }
        }

        return $bestCategory;
    }

    /**
     * @param list<string> $tokens
     * @param list<string> $wordsToRemove
     *
     * @return list<string>
     */
    private function removeLeadingWords(array $tokens, array $wordsToRemove): array
    {
        while ($tokens !== [] && in_array($tokens[0], $wordsToRemove, true)) {
            array_shift($tokens);
        }

        return $tokens;
    }

    private function singularizeToken(string $token): string
    {
        if (
            mb_strlen($token) <= 3
            || in_array($token, self::SINGULARIZATION_EXCEPTIONS, true)
        ) {
            return $token;
        }

        if (str_ends_with($token, 's') || str_ends_with($token, 'x')) {
            return mb_substr($token, 0, -1);
        }

        return $token;
    }
}
