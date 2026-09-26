<?php

namespace App\Service\Weight;

use App\DTO\WeightGoalProjectionData;
use App\DTO\WeightProjectionData;
use App\Entity\Milestone;
use App\Entity\Profile;
use App\Entity\WeightEntry;
use App\Service\Milestone\MilestoneService;

final readonly class WeightProjectionService
{
    private const MINIMUM_MEASUREMENT_COUNT = 3;
    private const MINIMUM_OBSERVATION_PERIOD_IN_DAYS = 7;
    private const MAXIMUM_PROJECTION_PERIOD_IN_DAYS = 5 * 365;
    private const MINIMUM_DAILY_WEIGHT_LOSS = 0.001;

    public function __construct(private MilestoneService $milestoneService)
    {
    }

    /**
     * @param list<WeightEntry> $weightEntries
     */
    public function project(Profile $profile, array $weightEntries): WeightProjectionData
    {
        $validWeightEntries = array_values(array_filter(
            $weightEntries,
            static fn (WeightEntry $weightEntry): bool => $weightEntry->getWeight() !== null,
        ));

        usort(
            $validWeightEntries,
            static fn (WeightEntry $firstEntry, WeightEntry $secondEntry): int => $firstEntry->getMeasuredAt() <=> $secondEntry->getMeasuredAt(),
        );

        $measurementCount = count($validWeightEntries);
        if ($measurementCount < self::MINIMUM_MEASUREMENT_COUNT) {
            return $this->unavailable(
                $measurementCount,
                0,
                'Au moins 3 pesées sont nécessaires pour calculer une projection indicative.',
            );
        }

        $firstMeasurementDate = $validWeightEntries[0]->getMeasuredAt();
        $latestWeightEntry = $validWeightEntries[array_key_last($validWeightEntries)];
        $observationPeriodInDays = (int) $firstMeasurementDate
            ->setTime(0, 0)
            ->diff($latestWeightEntry->getMeasuredAt()->setTime(0, 0))
            ->format('%a');

        if ($observationPeriodInDays < self::MINIMUM_OBSERVATION_PERIOD_IN_DAYS) {
            return $this->unavailable(
                $measurementCount,
                $observationPeriodInDays,
                'Les pesées doivent couvrir au moins 7 jours pour calculer une projection indicative.',
            );
        }

        $dailyWeightChange = $this->calculateDailyWeightChange($validWeightEntries, $firstMeasurementDate);
        if ($dailyWeightChange >= -self::MINIMUM_DAILY_WEIGHT_LOSS) {
            return $this->unavailable(
                $measurementCount,
                $observationPeriodInDays,
                'La tendance récente ne descend pas suffisamment pour projeter une date vers les objectifs.',
                $dailyWeightChange * 7,
            );
        }

        $currentWeight = (float) $latestWeightEntry->getWeight();
        $goals = $this->createGoalProjections(
            $profile,
            $currentWeight,
            $latestWeightEntry->getMeasuredAt(),
            $dailyWeightChange,
        );

        if ($goals === []) {
            return $this->unavailable(
                $measurementCount,
                $observationPeriodInDays,
                'Aucun objectif à venir ne peut actuellement faire l’objet d’une projection.',
                $dailyWeightChange * 7,
            );
        }

        return new WeightProjectionData(
            goals: $goals,
            measurementCount: $measurementCount,
            observationPeriodInDays: $observationPeriodInDays,
            weeklyWeightChange: round($dailyWeightChange * 7, 2),
        );
    }

    /**
     * @param list<WeightEntry> $weightEntries
     */
    private function calculateDailyWeightChange(array $weightEntries, \DateTimeImmutable $firstMeasurementDate): float
    {
        $dayOffsets = [];
        $weights = [];

        foreach ($weightEntries as $weightEntry) {
            $dayOffsets[] = ($weightEntry->getMeasuredAt()->getTimestamp() - $firstMeasurementDate->getTimestamp()) / 86400;
            $weights[] = (float) $weightEntry->getWeight();
        }

        $averageDayOffset = array_sum($dayOffsets) / count($dayOffsets);
        $averageWeight = array_sum($weights) / count($weights);
        $covariance = 0.0;
        $dayOffsetVariance = 0.0;

        foreach ($dayOffsets as $index => $dayOffset) {
            $centeredDayOffset = $dayOffset - $averageDayOffset;
            $covariance += $centeredDayOffset * ($weights[$index] - $averageWeight);
            $dayOffsetVariance += $centeredDayOffset ** 2;
        }

        return $dayOffsetVariance > 0 ? $covariance / $dayOffsetVariance : 0.0;
    }

    /** @return list<WeightGoalProjectionData> */
    private function createGoalProjections(
        Profile $profile,
        float $currentWeight,
        \DateTimeImmutable $latestMeasurementDate,
        float $dailyWeightChange,
    ): array {
        $goalProjections = [];

        $finalTargetProjection = $this->createGoalProjection(
            label: 'Poids objectif',
            targetWeight: $profile->getTargetWeight(),
            currentWeight: $currentWeight,
            latestMeasurementDate: $latestMeasurementDate,
            dailyWeightChange: $dailyWeightChange,
            isFinalTarget: true,
        );
        if ($finalTargetProjection !== null) {
            $goalProjections[] = $finalTargetProjection;
        }

        $pendingMilestones = array_filter(
            $profile->getMilestones()->toArray(),
            fn (Milestone $milestone): bool => $milestone->getAchievedAt() === null
                && $this->milestoneService->isWeightMilestone($milestone)
                && $milestone->getTargetValue() < $currentWeight,
        );
        usort(
            $pendingMilestones,
            static fn (Milestone $firstMilestone, Milestone $secondMilestone): int => $secondMilestone->getTargetValue() <=> $firstMilestone->getTargetValue(),
        );

        foreach ($pendingMilestones as $milestone) {
            $milestoneProjection = $this->createGoalProjection(
                label: (string) $milestone->getTitle(),
                targetWeight: $milestone->getTargetValue(),
                currentWeight: $currentWeight,
                latestMeasurementDate: $latestMeasurementDate,
                dailyWeightChange: $dailyWeightChange,
                isFinalTarget: false,
                milestoneId: $milestone->getId(),
            );
            if ($milestoneProjection !== null) {
                $goalProjections[] = $milestoneProjection;
            }
        }

        return $goalProjections;
    }

    private function createGoalProjection(
        string $label,
        float $targetWeight,
        float $currentWeight,
        \DateTimeImmutable $latestMeasurementDate,
        float $dailyWeightChange,
        bool $isFinalTarget,
        ?int $milestoneId = null,
    ): ?WeightGoalProjectionData {
        if ($targetWeight >= $currentWeight) {
            return null;
        }

        $projectedDayCount = (int) ceil(($targetWeight - $currentWeight) / $dailyWeightChange);
        if ($projectedDayCount < 1 || $projectedDayCount > self::MAXIMUM_PROJECTION_PERIOD_IN_DAYS) {
            return null;
        }

        return new WeightGoalProjectionData(
            label: $label,
            targetWeight: $targetWeight,
            projectedDate: $latestMeasurementDate->modify(sprintf('+%d days', $projectedDayCount)),
            isFinalTarget: $isFinalTarget,
            milestoneId: $milestoneId,
        );
    }

    private function unavailable(
        int $measurementCount,
        int $observationPeriodInDays,
        string $reason,
        ?float $weeklyWeightChange = null,
    ): WeightProjectionData {
        return new WeightProjectionData(
            goals: [],
            measurementCount: $measurementCount,
            observationPeriodInDays: $observationPeriodInDays,
            weeklyWeightChange: $weeklyWeightChange !== null ? round($weeklyWeightChange, 2) : null,
            unavailableReason: $reason,
        );
    }
}
