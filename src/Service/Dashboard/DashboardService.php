<?php

namespace App\Service\Dashboard;

use App\DTO\DailyCheckinData;
use App\DTO\DashboardData;
use App\DTO\DrinkEntryData;
use App\DTO\FoodEventData;
use App\DTO\MilestoneData;
use App\Entity\Activity;
use App\Repository\DailyCheckinRepository;
use App\Repository\DrinkEntryRepository;
use App\Repository\FoodEventRepository;
use App\Repository\ProfileRepository;
use App\Repository\WeightEntryRepository;
use App\Service\Milestone\MilestoneService;
use App\DTO\SleepEntryData;
use App\Repository\SleepEntryRepository;
use App\DTO\ActivityData;
use App\Repository\ActivityRepository;

final readonly class DashboardService
{
    public function __construct(
        private ProfileRepository $profileRepository,
        private WeightEntryRepository $weightEntryRepository,
        private MilestoneService $milestoneService,
        private FoodEventRepository $foodEventRepository,
        private DrinkEntryRepository $drinkEntryRepository,
        private DailyCheckinRepository $dailyCheckinRepository,
        private SleepEntryRepository $sleepEntryRepository,
        private ActivityRepository $activityRepository,
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

        $lostWeight = 0;
        $remainingWeight = $profile->getStartingWeight() - $profile->getTargetWeight();
        $progressPercentage = 0;

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

        $nextMilestone = null;

        if ($currentWeight !== null) {
            $milestone = $this->milestoneService->findNextMilestone(
                $profile,
                $currentWeight
            );

            if ($milestone !== null) {
                $nextMilestone = new MilestoneData(
                    title: $milestone->getTitle(),
                    description: $milestone->getDescription(),
                    targetValue: $milestone->getTargetValue(),
                    remainingWeight: $currentWeight - $milestone->getTargetValue(),
                );
            }
        }

        $recentMeals = $this->foodEventRepository
            ->findBy(
                [],
                ['eatenAt' => 'DESC'],
                5
            );

        $recentMeals = array_map(
            fn ($foodEvent) => new FoodEventData(
                $foodEvent->getMealType(),
                $foodEvent->getEatenAt(),
                $foodEvent->getDescription(),
                $foodEvent->getHungerLevel(),
                $foodEvent->getPleasureLevel(),
            ),
            $recentMeals
        );

        $recentDrinks = $this->drinkEntryRepository
            ->findBy(
                [],
                ['date' => 'DESC'],
                5
            );

        $recentDrinks = array_map(
            fn ($drink) => new DrinkEntryData(
                $drink->getDrinkType(),
                $drink->getQuantity(),
                $drink->getDate(),
                $drink->getDescription(),
            ),
            $recentDrinks
        );

        $dailyCheckin = $this->dailyCheckinRepository
            ->findOneBy(
                [],
                [
                    'date' => 'DESC'
                ]
            );

        $dailyCheckinData = null;

        if ($dailyCheckin) {
            $dailyCheckinData = new DailyCheckinData(
                $dailyCheckin->getDate(),
                $dailyCheckin->getMoodLevel(),
                $dailyCheckin->getEnergyLevel(),
                $dailyCheckin->getFrustrationLevel(),
                $dailyCheckin->getPainLevel(),
                $dailyCheckin->getNote(),
            );
        }

        $sleepEntry = $this->sleepEntryRepository
            ->findOneBy(
                [],
                ['date' => 'DESC']
            );

        $sleepEntryData = null;

        if ($sleepEntry) {
            $bedTime = $sleepEntry->getBedTime();
            $wakeUpTime = $sleepEntry->getWakeUpTime();

            $bedMinutes = ((int) $bedTime->format('H') * 60)
                + (int) $bedTime->format('i');

            $wakeUpMinutes = ((int) $wakeUpTime->format('H') * 60)
                + (int) $wakeUpTime->format('i');

            if ($wakeUpMinutes < $bedMinutes) {
                $wakeUpMinutes += 24 * 60;
            }

            $durationMinutes = $wakeUpMinutes - $bedMinutes;

            $sleepEntryData = new SleepEntryData(
                $sleepEntry->getDate(),
                $bedTime,
                $wakeUpTime,
                $sleepEntry->getQuality(),
                $sleepEntry->getNote(),
                $durationMinutes,
            );
        }

        $activities = $this->activityRepository->findBy(
            [],
            ['date' => 'DESC'],
            5
        );

        $recentActivities = array_map(
            static function (Activity $activity): ActivityData {
                return new ActivityData(
                    $activity->getDate(),
                    $activity->getDescription(),
                    $activity->getPainLevel(),
                    $activity->getNote(),
                );
            },
            $activities
        );

        $imc = round($profile->getStartingWeight() / ($profile->getHeight() / 100) ** 2,2);
        $targetImc = round($profile->getTargetWeight() / ($profile->getHeight() / 100) ** 2,2);

        return new DashboardData(
            height: $profile->getHeight(),
            startingWeight: $profile->getStartingWeight(),
            targetWeight: $profile->getTargetWeight(),
            currentWeight: $currentWeight ?? $profile->getStartingWeight(),
            biologicalGender: $profile->getBiologicalGender(),
            lostWeight: $lostWeight,
            remainingWeight: $remainingWeight,
            progressPercentage: $progressPercentage,
            nextMilestone: $nextMilestone,
            recentMeals: $recentMeals,
            recentDrinks: $recentDrinks,
            dailyCheckin: $dailyCheckinData,
            sleep: $sleepEntryData,
            recentActivities: $recentActivities,
            imc: $imc,
            targetImc: $targetImc,
        );
    }
}