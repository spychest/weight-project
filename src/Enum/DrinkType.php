<?php

namespace App\Enum;

enum DrinkType: string
{
    case WATER = 'eau';
    case SODA = 'soda';
    case SUGAR_FREE_SODA = 'soda_sans_sucre';
    case JUICE = 'jus';
    case COFFEE = 'café';
    case TEA = 'thé';
    case OTHER = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::WATER => 'Eau',
            self::SODA => 'Soda',
            self::SUGAR_FREE_SODA => 'Soda sans sucre',
            self::JUICE => 'Jus',
            self::COFFEE => 'Café',
            self::TEA => 'Thé',
            self::OTHER => 'Autre',
        };
    }
}
