<?php

namespace App\DTO;

final readonly class MilestoneData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public float $targetValue,
        public float $remainingWeight,
    ) {
    }
}