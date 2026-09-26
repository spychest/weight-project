<?php

namespace App\DTO;

final readonly class WeightProjectionData
{
    /**
     * @param list<WeightGoalProjectionData> $goals
     */
    public function __construct(
        public array $goals,
        public int $measurementCount,
        public int $observationPeriodInDays,
        public ?float $weeklyWeightChange,
        public ?string $unavailableReason = null,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->unavailableReason === null && $this->goals !== [];
    }
}
