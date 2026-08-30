<?php

namespace App\Service\Dashboard;

use App\DTO\DailyCheckinData;
use App\DTO\DashboardData;
use App\DTO\DrinkEntryData;
use App\DTO\FoodEventData;
use App\DTO\MilestoneData;
use App\Entity\Activity;
use App\Entity\DailyCheckin;
use App\Entity\DrinkEntry;
use App\Entity\FoodEvent;
use App\Entity\Profile;
use App\Entity\SleepEntry;
use App\Repository\DailyCheckinRepository;
use App\Repository\DrinkEntryRepository;
use App\Repository\FoodEventRepository;
use App\Repository\WeightEntryRepository;
use App\Service\Milestone\MilestoneService;
use App\DTO\SleepEntryData;
use App\Repository\SleepEntryRepository;
use App\DTO\ActivityData;
use App\Repository\ActivityRepository;

final readonly class DashboardService
{
    public function __construct(
        private WeightEntryRepository $weightEntryRepository,
        private MilestoneService $milestoneService,
        private FoodEventRepository $foodEventRepository,
        private DrinkEntryRepository $drinkEntryRepository,
        private DailyCheckinRepository $dailyCheckinRepository,
        private SleepEntryRepository $sleepEntryRepository,
        private ActivityRepository $activityRepository,
    ) {
    }

    public function getDashboardForProfile(Profile $profile): DashboardData
    {
        $latestWeightEntry = $this->weightEntryRepository->findLatestForProfile($profile);

        $lostWeight = 0;
        $remainingWeight = $profile->getStartingWeight() - $profile->getTargetWeight();
        $progressPercentage = 0;

        $currentWeight = $latestWeightEntry?->getWeight();

        if ($currentWeight !== null) {
            $lostWeight = $profile->getStartingWeight() - $currentWeight;
            $remainingWeight = $currentWeight - $profile->getTargetWeight();
            $totalWeightToLose = $profile->getStartingWeight() - $profile->getTargetWeight();

            $progressPercentage = ($lostWeight / $totalWeightToLose) * 100;
        }

        $nextMilestone = null;

        if ($currentWeight !== null) {
            $nextMilestoneEntity = $this->milestoneService->findNextMilestone(
                $profile,
                $currentWeight,
            );

            if ($nextMilestoneEntity !== null) {
                $nextMilestone = new MilestoneData(
                    title: $nextMilestoneEntity->getTitle(),
                    description: $nextMilestoneEntity->getDescription(),
                    targetValue: $nextMilestoneEntity->getTargetValue(),
                    remainingWeight: $currentWeight - $nextMilestoneEntity->getTargetValue(),
                );
            }
        }

        $recentMealData = array_map(
            static fn (FoodEvent $foodEvent): FoodEventData => new FoodEventData(
                $foodEvent->getMealType(),
                $foodEvent->getEatenAt(),
                $foodEvent->getDescription(),
                $foodEvent->getHungerLevel(),
                $foodEvent->getPleasureLevel(),
            ),
            $this->foodEventRepository->findRecentForProfile($profile, 5),
        );

        $recentDrinkData = array_map(
            static fn (DrinkEntry $drinkEntry): DrinkEntryData => new DrinkEntryData(
                $drinkEntry->getDrinkType(),
                $drinkEntry->getQuantity(),
                $drinkEntry->getDate(),
                $drinkEntry->getDescription(),
            ),
            $this->drinkEntryRepository->findRecentForProfile($profile, 5),
        );

        $latestDailyCheckin = $this->dailyCheckinRepository->findLatestForProfile($profile);
        $latestDailyCheckinData = $this->createDailyCheckinData($latestDailyCheckin);

        $latestSleepEntry = $this->sleepEntryRepository->findLatestForProfile($profile);
        $latestSleepEntryData = $this->createSleepEntryData($latestSleepEntry);

        $recentActivityEntities = $this->activityRepository->findRecentForProfile($profile, 5);

        $recentActivities = array_map(
            static function (Activity $activity): ActivityData {
                return new ActivityData(
                    $activity->getDate(),
                    $activity->getDescription(),
                    $activity->getPainLevel(),
                    $activity->getNote(),
                );
            },
            $recentActivityEntities,
        );

        $startingBodyMassIndex = $this->calculateBodyMassIndex($profile, $profile->getStartingWeight());
        $currentBodyMassIndex = $this->calculateBodyMassIndex($profile, $currentWeight ?? $profile->getStartingWeight());
        $targetBodyMassIndex = $this->calculateBodyMassIndex($profile, $profile->getTargetWeight());

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
            recentMeals: $recentMealData,
            recentDrinks: $recentDrinkData,
            dailyCheckin: $latestDailyCheckinData,
            sleep: $latestSleepEntryData,
            recentActivities: $recentActivities,
            imc: $startingBodyMassIndex,
            currentImc: $currentBodyMassIndex,
            targetImc: $targetBodyMassIndex,
        );
    }

    private function createDailyCheckinData(?DailyCheckin $dailyCheckin): ?DailyCheckinData
    {
        if ($dailyCheckin === null) {
            return null;
        }

        return new DailyCheckinData(
            $dailyCheckin->getDate(),
            $dailyCheckin->getMoodLevel(),
            $dailyCheckin->getEnergyLevel(),
            $dailyCheckin->getFrustrationLevel(),
            $dailyCheckin->getPainLevel(),
            $dailyCheckin->getNote(),
        );
    }

    private function createSleepEntryData(?SleepEntry $sleepEntry): ?SleepEntryData
    {
        if ($sleepEntry === null) {
            return null;
        }

        $bedTime = $sleepEntry->getBedTime();
        $wakeUpTime = $sleepEntry->getWakeUpTime();
        $bedTimeInMinutes = ((int) $bedTime->format('H') * 60) + (int) $bedTime->format('i');
        $wakeUpTimeInMinutes = ((int) $wakeUpTime->format('H') * 60) + (int) $wakeUpTime->format('i');

        if ($wakeUpTimeInMinutes < $bedTimeInMinutes) {
            $wakeUpTimeInMinutes += 24 * 60;
        }

        return new SleepEntryData(
            $sleepEntry->getDate(),
            $bedTime,
            $wakeUpTime,
            $sleepEntry->getQuality(),
            $sleepEntry->getNote(),
            $wakeUpTimeInMinutes - $bedTimeInMinutes,
        );
    }

    private function calculateBodyMassIndex(Profile $profile, float $weight): float
    {
        $heightInMeters = $profile->getHeight() / 100;

        return round($weight / $heightInMeters ** 2, 2);
    }
}
