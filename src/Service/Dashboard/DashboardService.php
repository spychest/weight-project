<?php

namespace App\Service\Dashboard;

use App\DTO\DashboardData;
use App\Repository\ProfileRepository;

final readonly class DashboardService
{
    public function __construct(
        private ProfileRepository $profileRepository,
    )
    {
    }

    public function getDashboard(): DashboardData
    {
        $profile = $this->profileRepository->findOneBy([]);

        if(null === $profile) {
            throw new \LogicException('No profile found.');
        }

        return new DashboardData(
            currentWeight: null,
            targetWeight: $profile->getTargetWeight(),
            height: $profile->getHeight(),
            biologicalGender: $profile->getBiologicalGender(),
        );
    }
}