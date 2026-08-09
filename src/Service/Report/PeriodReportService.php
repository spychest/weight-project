<?php

namespace App\Service\Report;

use App\DTO\Report\CheckinEntryReportData;
use App\DTO\Report\CheckinReportData;
use App\DTO\Report\FoodEntryReportData;
use App\DTO\Report\FoodReportData;
use App\DTO\Report\PeriodReportData;
use App\DTO\Report\WeightEntryReportData;
use App\DTO\Report\WeightReportData;
use App\Entity\Profile;
use App\Repository\DailyCheckinRepository;
use App\Repository\FoodEventRepository;
use App\Repository\WeightEntryRepository;
use App\DTO\Report\DrinkEntryReportData;
use App\DTO\Report\HydrationReportData;
use App\Repository\DrinkEntryRepository;
use App\DTO\Report\SleepEntryReportData;
use App\DTO\Report\SleepReportData;
use App\Repository\SleepEntryRepository;
use App\DTO\Report\ActivityEntryReportData;
use App\DTO\Report\ActivityReportData;
use App\Repository\ActivityRepository;
use App\DTO\Report\MilestoneEntryReportData;
use App\DTO\Report\MilestoneReportData;
use App\Repository\MilestoneRepository;

class PeriodReportService
{
    public function __construct(
        private readonly WeightEntryRepository $weightEntryRepository,
        private readonly DrinkEntryRepository $drinkEntryRepository,
        private readonly FoodEventRepository $foodEventRepository,
        private readonly DailyCheckinRepository $dailyCheckinRepository,
        private readonly SleepEntryRepository $sleepEntryRepository,
        private readonly ActivityRepository $activityRepository,
        private readonly MilestoneRepository $milestoneRepository,
    ) {
    }

    public function generate(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): PeriodReportData {
        return new PeriodReportData(
            startDate: $startDate,
            endDate: $endDate,
            weight: $this->buildWeightReport(
                $profile,
                $startDate,
                $endDate,
            ),
            hydration: $this->buildHydrationReport(
                $profile,
                $startDate,
                $endDate,
            ),
            food: $this->buildFoodReport(
                $profile,
                $startDate,
                $endDate,
            ),
            checkin: $this->buildCheckinReport(
                $profile,
                $startDate,
                $endDate,
            ),
            sleep: $this->buildSleepReport(
                $profile,
                $startDate,
                $endDate,
            ),
            activity: $this->buildActivityReport(
                $profile,
                $startDate,
                $endDate,
            ),
            milestone: $this->buildMilestoneReport(
                $profile,
                $startDate,
                $endDate,
            ),
        );
    }

    private function buildWeightReport(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): WeightReportData {
        $entries = $this->weightEntryRepository->findForPeriod(
            $profile,
            $startDate,
            $endDate,
        );

        $entryCount = count($entries);

        if ($entryCount === 0) {
            return new WeightReportData(
                entryCount: 0,
                initialWeight: null,
                finalWeight: null,
                change: null,
                entries: [],
            );
        }

        $reportEntries = [];

        foreach ($entries as $entry) {
            $reportEntries[] = new WeightEntryReportData(
                measuredAt: $entry->getMeasuredAt(),
                weight: $entry->getWeight(),
                note: $entry->getNote(),
            );
        }

        $initialWeight = $entries[0]->getWeight();
        $finalWeight = $entries[$entryCount - 1]->getWeight();

        return new WeightReportData(
            entryCount: $entryCount,
            initialWeight: $initialWeight,
            finalWeight: $finalWeight,
            change: $finalWeight - $initialWeight,
            entries: $reportEntries,
        );
    }

    private function buildHydrationReport(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): HydrationReportData {
        $entries = $this->drinkEntryRepository->findForPeriod(
            $profile,
            $startDate,
            $endDate,
        );

        $totalDays = $startDate->diff($endDate)->days + 1;

        if ($entries === []) {
            return new HydrationReportData(
                totalQuantity: 0,
                averageDailyQuantity: 0.0,
                daysWithEntries: 0,
                totalDays: $totalDays,
                entries: [],
            );
        }

        $totalQuantity = 0;
        $datesWithEntries = [];

        $reportEntries = [];

        foreach ($entries as $entry) {
            $totalQuantity += $entry->getQuantity();

            $dateKey = $entry->getDate()->format('Y-m-d');
            $datesWithEntries[$dateKey] = true;

            $reportEntries[] = new DrinkEntryReportData(
                date: $entry->getDate(),
                drinkType: $entry->getDrinkType(),
                quantity: $entry->getQuantity(),
                description: $entry->getDescription(),
                note: $entry->getNote(),
            );
        }

        $daysWithEntries = count($datesWithEntries);

        $averageDailyQuantity = $totalQuantity / $totalDays;

        return new HydrationReportData(
            totalQuantity: $totalQuantity,
            averageDailyQuantity: $averageDailyQuantity,
            daysWithEntries: $daysWithEntries,
            totalDays: $totalDays,
            entries: $reportEntries,
        );
    }

    private function buildFoodReport(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): FoodReportData {
        $entries = $this->foodEventRepository->findForPeriod(
            $profile,
            $startDate,
            $endDate,
        );

        if ($entries === []) {
            return new FoodReportData(
                entryCount: 0,
                averageHungerLevel: null,
                averagePleasureLevel: null,
                mealTypeCounts: [],
                entries: [],
            );
        }

        $hungerTotal = 0;
        $hungerCount = 0;

        $pleasureTotal = 0;
        $pleasureCount = 0;

        $mealTypeCounts = [];

        $reportEntries = [];

        foreach ($entries as $entry) {
            $hungerLevel = $entry->getHungerLevel();
            $pleasureLevel = $entry->getPleasureLevel();
            $mealType = $entry->getMealType();

            if ($mealType !== null) {
                $mealTypeValue = $mealType->value;

                $mealTypeCounts[$mealTypeValue] =
                    ($mealTypeCounts[$mealTypeValue] ?? 0) + 1;
            }

            if ($hungerLevel !== null) {
                $hungerTotal += $hungerLevel;
                $hungerCount++;
            }

            if ($pleasureLevel !== null) {
                $pleasureTotal += $pleasureLevel;
                $pleasureCount++;
            }

            $reportEntries[] = new FoodEntryReportData(
                date: $entry->getEatenAt(),
                mealType: $mealType->value,
                description: $entry->getDescription(),
                hungerLevel: $hungerLevel,
                pleasureLevel: $pleasureLevel,
                cause: $entry->getCause(),
                note: $entry->getNote(),
            );
        }

        $averageHungerLevel = $hungerCount > 0
            ? $hungerTotal / $hungerCount
            : null;

        $averagePleasureLevel = $pleasureCount > 0
            ? $pleasureTotal / $pleasureCount
            : null;

        return new FoodReportData(
            entryCount: count($entries),
            averageHungerLevel: $averageHungerLevel,
            averagePleasureLevel: $averagePleasureLevel,
            mealTypeCounts: $mealTypeCounts,
            entries: $reportEntries,
        );
    }

    private function buildCheckinReport(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): CheckinReportData {
        $entries = $this->dailyCheckinRepository->findForPeriod(
            $profile,
            $startDate,
            $endDate,
        );

        $totalDays = $startDate->diff($endDate)->days + 1;

        if ($entries === []) {
            return new CheckinReportData(
                totalDays: $totalDays,
                daysWithEntries: 0,
                averageMoodLevel: null,
                averageEnergyLevel: null,
                averageFrustrationLevel: null,
                averagePainLevel: null,
                entries: [],
            );
        }

        $moodTotal = 0;
        $energyTotal = 0;
        $frustrationTotal = 0;

        $painTotal = 0;
        $painCount = 0;

        $reportEntries = [];

        foreach ($entries as $entry) {
            $moodTotal += $entry->getMoodLevel();
            $energyTotal += $entry->getEnergyLevel();
            $frustrationTotal += $entry->getFrustrationLevel();

            $painLevel = $entry->getPainLevel();

            if ($painLevel !== null) {
                $painTotal += $painLevel;
                $painCount++;
            }

            $reportEntries[] = new CheckinEntryReportData(
                date: $entry->getDate(),
                moodLevel: $entry->getMoodLevel(),
                energyLevel: $entry->getEnergyLevel(),
                frustrationLevel: $entry->getFrustrationLevel(),
                painLevel: $painLevel,
                note: $entry->getNote(),
            );
        }

        $entryCount = count($entries);

        $averageMoodLevel = $moodTotal / $entryCount;
        $averageEnergyLevel = $energyTotal / $entryCount;
        $averageFrustrationLevel = $frustrationTotal / $entryCount;

        $averagePainLevel = $painCount > 0
            ? $painTotal / $painCount
            : null;

        return new CheckinReportData(
            totalDays: $totalDays,
            daysWithEntries: $entryCount,
            averageMoodLevel: $averageMoodLevel,
            averageEnergyLevel: $averageEnergyLevel,
            averageFrustrationLevel: $averageFrustrationLevel,
            averagePainLevel: $averagePainLevel,
            entries: $reportEntries,
        );
    }

    private function buildSleepReport(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): SleepReportData {
        $entries = $this->sleepEntryRepository->findForPeriod(
            $profile,
            $startDate,
            $endDate,
        );

        $totalDays = $startDate->diff($endDate)->days + 1;

        if ($entries === []) {
            return new SleepReportData(
                totalDays: $totalDays,
                nightsWithEntries: 0,
                averageDurationInMinutes: null,
                averageQuality: null,
                shortestDurationInMinutes: null,
                longestDurationInMinutes: null,
                entries: [],
            );
        }

        $totalDurationInMinutes = 0;
        $totalQuality = 0;

        $shortestDurationInMinutes = null;
        $longestDurationInMinutes = null;

        $reportEntries = [];

        foreach ($entries as $entry) {
            $bedTime = $entry->getBedTime();
            $wakeUpTime = $entry->getWakeUpTime();

            $durationInMinutes = $this->calculateSleepDuration(
                $bedTime,
                $wakeUpTime,
            );

            $quality = $entry->getQuality();

            $totalDurationInMinutes += $durationInMinutes;
            $totalQuality += $quality;

            if (
                $shortestDurationInMinutes === null
                || $durationInMinutes < $shortestDurationInMinutes
            ) {
                $shortestDurationInMinutes = $durationInMinutes;
            }

            if (
                $longestDurationInMinutes === null
                || $durationInMinutes > $longestDurationInMinutes
            ) {
                $longestDurationInMinutes = $durationInMinutes;
            }

            $reportEntries[] = new SleepEntryReportData(
                date: $entry->getDate(),
                bedTime: $bedTime,
                wakeUpTime: $wakeUpTime,
                durationInMinutes: $durationInMinutes,
                quality: $quality,
                note: $entry->getNote(),
            );
        }

        $entryCount = count($entries);

        return new SleepReportData(
            totalDays: $totalDays,
            nightsWithEntries: $entryCount,
            averageDurationInMinutes: (int) round(
                $totalDurationInMinutes / $entryCount
            ),
            averageQuality: $totalQuality / $entryCount,
            shortestDurationInMinutes: $shortestDurationInMinutes,
            longestDurationInMinutes: $longestDurationInMinutes,
            entries: $reportEntries,
        );
    }

    private function calculateSleepDuration(
        \DateTimeImmutable $bedTime,
        \DateTimeImmutable $wakeUpTime,
    ): int {
        $bedMinutes = ((int) $bedTime->format('H') * 60)
            + (int) $bedTime->format('i');

        $wakeUpMinutes = ((int) $wakeUpTime->format('H') * 60)
            + (int) $wakeUpTime->format('i');

        if ($wakeUpMinutes <= $bedMinutes) {
            $wakeUpMinutes += 24 * 60;
        }

        return $wakeUpMinutes - $bedMinutes;
    }

    private function buildActivityReport(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): ActivityReportData {
        $entries = $this->activityRepository->findForPeriod(
            $profile,
            $startDate,
            $endDate,
        );

        if ($entries === []) {
            return new ActivityReportData(
                entryCount: 0,
                averagePainLevel: null,
                entries: [],
            );
        }

        $painTotal = 0;
        $painCount = 0;

        $reportEntries = [];

        foreach ($entries as $entry) {
            $painLevel = $entry->getPainLevel();

            if ($painLevel !== null) {
                $painTotal += $painLevel;
                $painCount++;
            }

            $reportEntries[] = new ActivityEntryReportData(
                date: $entry->getDate(),
                description: $entry->getDescription(),
                painLevel: $painLevel,
                note: $entry->getNote(),
            );
        }

        $averagePainLevel = $painCount > 0
            ? $painTotal / $painCount
            : null;

        return new ActivityReportData(
            entryCount: count($entries),
            averagePainLevel: $averagePainLevel,
            entries: $reportEntries,
        );
    }

    private function buildMilestoneReport(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): MilestoneReportData {
        $milestones = $this->milestoneRepository->findForProfile($profile);

        $reportEntries = [];
        $achievedCount = 0;

        foreach ($milestones as $milestone) {
            $achievedAt = $milestone->getAchievedAt();

            if (
                $achievedAt !== null
                && $achievedAt >= $startDate
                && $achievedAt < $endDate->modify('+1 day')
            ) {
                $achievedCount++;
            }

            $reportEntries[] = new MilestoneEntryReportData(
                title: $milestone->getTitle(),
                description: $milestone->getDescription(),
                type: $milestone->getType(),
                targetValue: $milestone->getTargetValue(),
                achievedAt: $achievedAt,
            );
        }

        return new MilestoneReportData(
            totalCount: count($milestones),
            achievedCount: $achievedCount,
            entries: $reportEntries,
        );
    }
}