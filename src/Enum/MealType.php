<?php

namespace App\Enum;

enum MealType: string
{
    case BREAKFAST = 'breakfast';
    case LUNCH = 'lunch';
    case DINNER = 'dinner';
    case SNACK = 'snack';

    public function label(): string
    {
        return match ($this) {
            self::BREAKFAST => 'Petit déjeuner',
            self::LUNCH => 'Déjeuner',
            self::DINNER => 'Dîner',
            self::SNACK => 'Snack',
        };
    }
}
