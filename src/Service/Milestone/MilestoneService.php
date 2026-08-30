<?php

namespace App\Service\Milestone;

use App\Entity\Milestone;
use App\Entity\Profile;

class MilestoneService
{
    private const WEIGHT_MILESTONE_TYPES = ['WEIGHT', 'POIDS'];

    public function findNextMilestone(Profile $profile, float $currentWeight): ?Milestone
    {
        $nextMilestone = null;

        foreach ($profile->getMilestones() as $milestone) {
            if (!$this->isWeightMilestone($milestone) || $milestone->getAchievedAt() !== null) {
                continue;
            }

            if ($currentWeight <= $milestone->getTargetValue()) {
                continue;
            }

            if ($nextMilestone === null || $milestone->getTargetValue() > $nextMilestone->getTargetValue()) {
                $nextMilestone = $milestone;
            }
        }

        return $nextMilestone;
    }

    public function validateReachedWeightMilestones(
        Profile $profile,
        float $recordedWeight,
        \DateTimeImmutable $measurementDate,
    ): int {
        $validatedMilestoneCount = 0;

        foreach ($profile->getMilestones() as $milestone) {
            if (!$this->isWeightMilestone($milestone)) {
                continue;
            }

            if ($milestone->getAchievedAt() !== null) {
                continue;
            }

            if ($recordedWeight > $milestone->getTargetValue()) {
                continue;
            }

            $milestone->setAchievedAt($measurementDate);
            $validatedMilestoneCount++;
        }

        return $validatedMilestoneCount;
    }

    private function isWeightMilestone(Milestone $milestone): bool
    {
        $normalizedMilestoneType = strtoupper(trim((string) $milestone->getType()));

        return in_array($normalizedMilestoneType, self::WEIGHT_MILESTONE_TYPES, true);
    }
}
