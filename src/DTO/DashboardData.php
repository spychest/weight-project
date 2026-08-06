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