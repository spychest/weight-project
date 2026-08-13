<?php

namespace App\DTO;

final readonly class DashboardData
{
    public function __construct(
        public float $height,
        public float $startingWeight,
        public float $targetWeight,
        public ?float $currentWeight,
        public string $biologicalGender,
        public ?float $lostWeight,
        public ?float $remainingWeight,
        public ?float $progressPercentage,
        public ?MilestoneData $nextMilestone,
        public array $recentMeals,
        public array $recentDrinks,
        public ?DailyCheckinData $dailyCheckin,
        public ?SleepEntryData $sleep,
        public array $recentActivities,
        public float $imc,
        public float $targetImc,
    ) {
    }

    public function getFormattedProgressPercentage(): string
    {
        if ($this->progressPercentage === null) {
            return '-';
        }

        return number_format($this->progressPercentage, 1) . ' %';
    }
}