<?php

namespace App\DTO\Report;

use App\Enum\MealType;

final readonly class FoodEntryReportData
{
    public function __construct(
        public \DateTimeImmutable $date,
        public MealType $mealType,
        public string $description,
        public ?int $hungerLevel,
        public ?int $pleasureLevel,
        public ?string $cause,
        public ?string $note,
    ) {
    }
}