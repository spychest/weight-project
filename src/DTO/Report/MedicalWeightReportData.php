<?php

namespace App\DTO\Report;

final readonly class MedicalWeightReportData
{
    public function __construct(
        public \DateTimeImmutable $generatedAt,
        public \DateTimeImmutable $trackingStartDate,
        public \DateTimeImmutable $trackingEndDate,
        public int $age,
        public float $height,
        public ?float $startingWeight,
        public ?float $latestWeight,
        public ?\DateTimeImmutable $latestWeightDate,
        public bool $latestWeightIsDeclared,
        public ?float $totalChange,
        public ?float $totalChangePercentage,
        public ?float $averageWeeklyChange,
        public ?int $trackingDurationDays,
        public ?float $previousMeasurementChange,
        public ?float $thirtyDayChange,
        public int $measurementCount,
        public int $invalidMeasurementCount,
        public string $chartDataUri,
        public bool $hasGeneralTrend,
        public bool $hasRecentTrend,
        public int $foodEntryCount,
        public ?float $averageHungerLevel,
        public ?float $averagePleasureLevel,
        public ?string $habitSummary,
        public ?string $medicalNotes,
    ) {
    }
}
