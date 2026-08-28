<?php

namespace App\Service;

use App\Entity\Profile;
use App\Repository\DailyCheckinRepository;
use App\Repository\DrinkEntryRepository;
use App\Repository\FoodEventRepository;
use App\Repository\MilestoneRepository;
use App\Repository\SleepEntryRepository;
use App\Repository\WeightEntryRepository;

class GraphService
{
    public function __construct(
        private WeightEntryRepository $weightEntryRepository,
        private MilestoneRepository $milestoneRepository,
        private DrinkEntryRepository $drinkEntryRepository,
        private SleepEntryRepository $sleepEntryRepository,
        private FoodEventRepository $foodEventRepository,
        private DailyCheckinRepository $dailyCheckinRepository,
    ) {
    }

    public function getWeightGraphData(Profile $profile): array
    {
        $weightEntries = $this->weightEntryRepository->findAllForProfile($profile);

        $trend4Weeks = null;
        $trend8Weeks = null;
        $trendAll = null;

        if (!empty($weightEntries)) {
            $latestDate = end($weightEntries)->getMeasuredAt();

            $entries4Weeks = array_filter(
                $weightEntries,
                fn ($entry) => $entry->getMeasuredAt() >= $latestDate->modify('-4 weeks')
            );

            $entries8Weeks = array_filter(
                $weightEntries,
                fn ($entry) => $entry->getMeasuredAt() >= $latestDate->modify('-8 weeks')
            );

            $entries4Weeks = array_values($entries4Weeks);
            $entries8Weeks = array_values($entries8Weeks);

            $trend4Weeks = $this->calculateWeightTrend($entries4Weeks);
            $trend8Weeks = $this->calculateWeightTrend($entries8Weeks);
            $trendAll = $this->calculateWeightTrend($weightEntries);
        }

        $labels = [];
        $weights = [];

        foreach ($weightEntries as $entry) {
            $labels[] = $entry->getMeasuredAt()->format('d/m/Y');
            $weights[] = $entry->getWeight();
        }

        $currentWeight = !empty($weights)
            ? end($weights)
            : $profile->getStartingWeight();

        $nextMilestone = null;

        foreach ($this->milestoneRepository->findForProfile($profile) as $milestone) {
            if ($milestone->getTargetValue() < $currentWeight) {
                if (
                    $nextMilestone === null
                    || $milestone->getTargetValue() > $nextMilestone->getTargetValue()
                ) {
                    $nextMilestone = $milestone;
                }
            }
        }

        return [
            'labels' => $labels,
            'weights' => $weights,
            'milestone' => $nextMilestone?->getTargetValue(),
            'milestoneTitle' => $nextMilestone?->getTitle(),
            'trends' => [
                'fourWeeks' => $trend4Weeks,
                'eightWeeks' => $trend8Weeks,
                'all' => $trendAll,
            ],
        ];
    }

    public function getWeightDetailData(Profile $profile): array
    {
        $entries = $this->weightEntryRepository->findAllForProfile($profile);

        $data = [];

        foreach ($entries as $entry) {
            $data[] = [
                'date' => $entry->getMeasuredAt()->format('Y-m-d'),
                'label' => $entry->getMeasuredAt()->format('d/m/Y'),
                'weight' => $entry->getWeight(),
            ];
        }

        return $data;
    }

    private function calculateWeightTrend(array $entries): ?float
    {
        if (count($entries) < 2) {
            return null;
        }

        $firstDate = $entries[0]->getMeasuredAt();

        $xValues = [];
        $yValues = [];

        foreach ($entries as $entry) {
            $days = ($entry->getMeasuredAt()->getTimestamp() - $firstDate->getTimestamp()) / 86400;

            $xValues[] = $days / 7;
            $yValues[] = $entry->getWeight();
        }

        $count = count($xValues);

        $sumX = array_sum($xValues);
        $sumY = array_sum($yValues);

        $sumXY = 0;
        $sumXSquared = 0;

        for ($i = 0; $i < $count; $i++) {
            $sumXY += $xValues[$i] * $yValues[$i];
            $sumXSquared += $xValues[$i] ** 2;
        }

        $denominator = ($count * $sumXSquared) - ($sumX ** 2);

        if ($denominator == 0) {
            return null;
        }

        $slope = (($count * $sumXY) - ($sumX * $sumY)) / $denominator;

        return round($slope, 2);
    }

    public function getHydrationGraphData(Profile $profile): array
    {
        $endDate = new \DateTimeImmutable('today');
        $startDate = $endDate->modify('-6 days');

        $entries = $this->drinkEntryRepository->findForPeriod(
            $profile,
            $startDate,
            $endDate,
        );

        $totals = [];

        foreach ($entries as $entry) {
            $date = $entry->getDate()->format('Y-m-d');

            if (!isset($totals[$date])) {
                $totals[$date] = 0;
            }

            $totals[$date] += $entry->getQuantity();
        }

        $labels = [];
        $quantities = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->modify("+$i days");
            $key = $date->format('Y-m-d');

            $labels[] = $date->format('d/m');
            $quantities[] = $totals[$key] ?? null;
        }

        return [
            'labels' => $labels,
            'quantities' => $quantities,
            'reference' => 2500,
        ];
    }

    public function getHydrationDetailData(Profile $profile): array
    {
        $entries = $this->drinkEntryRepository->findAllForProfile($profile);

        $data = [];

        foreach ($entries as $entry) {
            $data[] = [
                'date' => $entry->getDate()->format('Y-m-d'),
                'quantity' => $entry->getQuantity(),
            ];
        }

        return $data;
    }

    public function getSleepGraphData(Profile $profile): array
    {
        $endDate = new \DateTimeImmutable('today');
        $startDate = $endDate->modify('-6 days');

        $entries = $this->sleepEntryRepository->findForPeriod(
            $profile,
            $startDate,
            $endDate,
        );

        $durations = [];

        foreach ($entries as $entry) {
            $date = $entry->getDate()->format('Y-m-d');

            $bedTime = $entry->getBedTime();
            $wakeUpTime = $entry->getWakeUpTime();

            $bedMinutes =
                ((int) $bedTime->format('H') * 60)
                + (int) $bedTime->format('i');

            $wakeMinutes =
                ((int) $wakeUpTime->format('H') * 60)
                + (int) $wakeUpTime->format('i');

            if ($wakeMinutes <= $bedMinutes) {
                $wakeMinutes += 24 * 60;
            }

            $durationInHours = ($wakeMinutes - $bedMinutes) / 60;

            $durations[$date] = round($durationInHours, 2);
        }

        $labels = [];
        $values = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->modify("+$i days");
            $key = $date->format('Y-m-d');

            $labels[] = $date->format('d/m');
            $values[] = $durations[$key] ?? null;
        }

        return [
            'labels' => $labels,
            'durations' => $values,
            'reference' => 7,
        ];
    }

    public function getSleepDetailData(Profile $profile): array
    {
        $entries = $this->sleepEntryRepository->findAllForProfile($profile);

        $data = [];

        foreach ($entries as $entry) {
            $bedTime = $entry->getBedTime();
            $wakeUpTime = $entry->getWakeUpTime();

            $bedMinutes =
                ((int) $bedTime->format('H') * 60)
                + (int) $bedTime->format('i');

            $wakeMinutes =
                ((int) $wakeUpTime->format('H') * 60)
                + (int) $wakeUpTime->format('i');

            if ($wakeMinutes <= $bedMinutes) {
                $wakeMinutes += 24 * 60;
            }

            $duration = ($wakeMinutes - $bedMinutes) / 60;

            $data[] = [
                'date' => $entry->getDate()->format('Y-m-d'),
                'duration' => round($duration, 2),
            ];
        }

        return $data;
    }

    public function getMealTypeGraphData(Profile $profile): array
    {
        $entries = $this->foodEventRepository->findAllForProfile($profile);

        $counts = [];

        foreach ($entries as $entry) {
            $mealType = $entry->getMealType();

            if ($mealType === null) {
                continue;
            }

            $label = $mealType->value;

            if (!isset($counts[$label])) {
                $counts[$label] = 0;
            }

            $counts[$label]++;
        }

        return [
            'labels' => array_keys($counts),
            'values' => array_values($counts),
        ];
    }

    public function getMealDetailData(Profile $profile): array
    {
        $entries = $this->foodEventRepository->findAllForProfile($profile);

        $data = [];

        foreach ($entries as $entry) {
            if ($entry->getMealType() === null) {
                continue;
            }

            $data[] = [
                'date' => $entry->getEatenAt()->format('Y-m-d'),
                'mealType' => $entry->getMealType()->value,
            ];
        }

        return $data;
    }

    public function getDailyCheckinGraphData(Profile $profile): array
    {
        $entries = $this->dailyCheckinRepository->findAllForProfile($profile);

        $labels = [];
        $mood = [];
        $energy = [];
        $frustration = [];
        $pain = [];

        foreach ($entries as $entry) {
            $labels[] = $entry->getDate()->format('d/m/Y');
            $mood[] = $entry->getMoodLevel();
            $energy[] = $entry->getEnergyLevel();
            $frustration[] = $entry->getFrustrationLevel();
            $pain[] = $entry->getPainLevel();
        }

        return [
            'labels' => $labels,
            'mood' => $mood,
            'energy' => $energy,
            'frustration' => $frustration,
            'pain' => $pain,
        ];
    }

    public function getDailyCheckinDetailData(Profile $profile): array
    {
        $entries = $this->dailyCheckinRepository->findAllForProfile($profile);

        $data = [];

        foreach ($entries as $entry) {
            $data[] = [
                'date' => $entry->getDate()->format('Y-m-d'),
                'label' => $entry->getDate()->format('d/m/Y'),
                'mood' => $entry->getMoodLevel(),
                'energy' => $entry->getEnergyLevel(),
                'frustration' => $entry->getFrustrationLevel(),
                'pain' => $entry->getPainLevel(),
            ];
        }

        return $data;
    }
}