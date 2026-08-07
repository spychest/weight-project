<?php

namespace App\DTO;

use App\Enum\MealType;

final readonly class FoodEventData
{
    public function __construct(
        public MealType $mealType,
        public \DateTimeImmutable $eatenAt,
        public string $description,
        public ?int $hungerLevel,
        public ?int $pleasureLevel,
    ) {
    }
}