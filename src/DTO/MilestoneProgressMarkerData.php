<?php

namespace App\DTO;

final readonly class MilestoneProgressMarkerData
{
    public function __construct(
        public int $id,
        public string $title,
        public float $targetWeight,
        public float $positionPercentage,
        public bool $isAchieved,
        public bool $isNextMilestone,
    ) {
    }
}
