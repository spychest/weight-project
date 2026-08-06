<?php

namespace App\DTO;

final readonly class DashboardData
{
    public function __construct(
        public ?float $currentWeight,
        public float $targetWeight,
        public float $height,
        public string $biologicalGender,
    ) {
    }
}