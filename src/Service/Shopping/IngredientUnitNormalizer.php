<?php

namespace App\Service\Shopping;

use function Symfony\Component\String\u;

final class IngredientUnitNormalizer
{
    /** @var array<string, array{unit: string, factor: float}> */
    private const UNIT_CONVERSIONS = [
        'kg' => ['unit' => 'g', 'factor' => 1000.0],
        'kilogramme' => ['unit' => 'g', 'factor' => 1000.0],
        'kilogrammes' => ['unit' => 'g', 'factor' => 1000.0],
        'g' => ['unit' => 'g', 'factor' => 1.0],
        'gr' => ['unit' => 'g', 'factor' => 1.0],
        'gramme' => ['unit' => 'g', 'factor' => 1.0],
        'grammes' => ['unit' => 'g', 'factor' => 1.0],
        'mg' => ['unit' => 'g', 'factor' => 0.001],
        'l' => ['unit' => 'ml', 'factor' => 1000.0],
        'litre' => ['unit' => 'ml', 'factor' => 1000.0],
        'litres' => ['unit' => 'ml', 'factor' => 1000.0],
        'dl' => ['unit' => 'ml', 'factor' => 100.0],
        'cl' => ['unit' => 'ml', 'factor' => 10.0],
        'ml' => ['unit' => 'ml', 'factor' => 1.0],
        'cuillere a soupe' => ['unit' => 'ml', 'factor' => 15.0],
        'cuilleres a soupe' => ['unit' => 'ml', 'factor' => 15.0],
        'c a s' => ['unit' => 'ml', 'factor' => 15.0],
        'c a soupe' => ['unit' => 'ml', 'factor' => 15.0],
        'cas' => ['unit' => 'ml', 'factor' => 15.0],
        'cs' => ['unit' => 'ml', 'factor' => 15.0],
        'tbsp' => ['unit' => 'ml', 'factor' => 15.0],
        'cuillere a cafe' => ['unit' => 'ml', 'factor' => 5.0],
        'cuilleres a cafe' => ['unit' => 'ml', 'factor' => 5.0],
        'c a c' => ['unit' => 'ml', 'factor' => 5.0],
        'c a cafe' => ['unit' => 'ml', 'factor' => 5.0],
        'cac' => ['unit' => 'ml', 'factor' => 5.0],
        'cc' => ['unit' => 'ml', 'factor' => 5.0],
        'tsp' => ['unit' => 'ml', 'factor' => 5.0],
        'piece' => ['unit' => 'pièce', 'factor' => 1.0],
        'pieces' => ['unit' => 'pièce', 'factor' => 1.0],
        'unite' => ['unit' => 'pièce', 'factor' => 1.0],
        'unites' => ['unit' => 'pièce', 'factor' => 1.0],
        'boite' => ['unit' => 'boîte', 'factor' => 1.0],
        'boites' => ['unit' => 'boîte', 'factor' => 1.0],
        'bocal' => ['unit' => 'bocal', 'factor' => 1.0],
        'bocaux' => ['unit' => 'bocal', 'factor' => 1.0],
        'gousse' => ['unit' => 'gousse', 'factor' => 1.0],
        'gousses' => ['unit' => 'gousse', 'factor' => 1.0],
        'pincee' => ['unit' => 'pincée', 'factor' => 1.0],
        'pincees' => ['unit' => 'pincée', 'factor' => 1.0],
        'pot' => ['unit' => 'pot', 'factor' => 1.0],
        'pots' => ['unit' => 'pot', 'factor' => 1.0],
        'sachet' => ['unit' => 'sachet', 'factor' => 1.0],
        'sachets' => ['unit' => 'sachet', 'factor' => 1.0],
        'tranche' => ['unit' => 'tranche', 'factor' => 1.0],
        'tranches' => ['unit' => 'tranche', 'factor' => 1.0],
    ];

    /** @return array{unit: string, factor: float} */
    public function normalize(string $unit): array
    {
        $normalizedUnit = u(trim($unit))->ascii()->lower()->toString();
        $normalizedUnit = preg_replace('/[^a-z0-9]+/', ' ', $normalizedUnit) ?? $normalizedUnit;
        $normalizedUnit = trim(preg_replace('/\s+/', ' ', $normalizedUnit) ?? $normalizedUnit);

        return self::UNIT_CONVERSIONS[$normalizedUnit] ?? [
            'unit' => mb_strtolower(trim($unit)),
            'factor' => 1.0,
        ];
    }
}
