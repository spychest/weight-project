<?php

namespace App\DTO\Report;

final readonly class FoodReportData
{
    public function __construct(
        public int $entryCount,
        public ?float $averageHungerLevel,
        public ?float $averagePleasureLevel,
        public array $mealTypeCounts,

        /** @var FoodEntryReportData[] */
        public array $entries,
    ) {
    }
}