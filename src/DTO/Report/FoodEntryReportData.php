<?php

namespace App\DTO\Report;


final readonly class FoodEntryReportData
{
    public function __construct(
        public \DateTimeImmutable $date,
        public string $mealType,
        public string $description,
        public ?int $hungerLevel,
        public ?int $pleasureLevel,
        public ?string $cause,
        public ?string $note,
    ) {
    }
}