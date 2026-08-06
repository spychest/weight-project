<?php

namespace App\Service\Milestone;

use App\Entity\Milestone;
use App\Entity\Profile;

class MilestoneService
{
    public function findNextMilestone(Profile $profile, float $currentWeight): ?Milestone
    {
        $nextMilestone = null;

        foreach ($profile->getMilestones() as $milestone) {
            if ($currentWeight <= $milestone->getTargetValue()) {
                continue;
            }

            if ($nextMilestone === null || $milestone->getTargetValue() > $nextMilestone->getTargetValue()) {
                $nextMilestone = $milestone;
            }
        }

        return $nextMilestone;
    }
}