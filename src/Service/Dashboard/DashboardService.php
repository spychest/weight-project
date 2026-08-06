<?php

namespace App\Service\Dashboard;

use App\DTO\DashboardData;
use App\Repository\ProfileRepository;
use App\Repository\WeightEntryRepository;

final readonly class DashboardService
{
    public function __construct(
        private ProfileRepository $profileRepository,
        private WeightEntryRepository $weightEntryRepository,
    )
    {
    }

    public function getDashboard(): DashboardData
    {
        $profile = $this->profileRepository->findOneBy([]);

        if(null === $profile) {
            throw new \LogicException('No profile found.');
        }

        $latestWeightEntry = $this->weightEntryRepository->findLatestForProfile($profile);

        $lostWeight = null;
        $remainingWeight = null;
        $progressPercentage = null;

        $currentWeight = $latestWeightEntry?->getWeight();

        if ($currentWeight !== null) {
            $lostWeight = $profile->getStartingWeight() - $currentWeight;
        }

        if ($currentWeight !== null) {
            $remainingWeight = $currentWeight - $profile->getTargetWeight();
        }

        if ($currentWeight !== null) {
            $totalToLose = $profile->getStartingWeight() - $profile->getTargetWeight();

            $progressPercentage = ($lostWeight / $totalToLose) * 100;
        }

        return new DashboardData(
            height: $profile->getHeight(),
            startingWeight: $profile->getStartingWeight(),
            targetWeight: $profile->getTargetWeight(),
            currentWeight: $currentWeight,
            biologicalGender: $profile->getBiologicalGender(),
            lostWeight: $lostWeight,
            remainingWeight: $remainingWeight,
            progressPercentage: $progressPercentage,
        );
    }
}