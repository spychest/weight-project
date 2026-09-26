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

    public function getFinalTargetProjection(): ?WeightGoalProjectionData
    {
        foreach ($this->goals as $goalProjection) {
            if ($goalProjection->isFinalTarget) {
                return $goalProjection;
            }
        }

        return null;
    }

    public function findMilestoneProjection(int $milestoneId): ?WeightGoalProjectionData
    {
        foreach ($this->goals as $goalProjection) {
            if ($goalProjection->milestoneId === $milestoneId) {
                return $goalProjection;
            }
        }

        return null;
    }
}
