<?php

namespace App\DTO;

final readonly class WeightGoalProjectionData
{
    public function __construct(
        public string $label,
        public float $targetWeight,
        public \DateTimeImmutable $projectedDate,
        public bool $isFinalTarget,
        public ?int $milestoneId = null,
    ) {
    }
}
