<?php

namespace App\Service\Report;

use App\DTO\Report\PeriodReportData;

final class ReportDataExporter
{
    public function export(PeriodReportData $report): string
    {
        $data = [
            'period' => [
                'startDate' => $report->startDate->format('Y-m-d'),
                'endDate' => $report->endDate->format('Y-m-d'),
            ],

            'weight' => [
                'entryCount' => $report->weight->entryCount,
                'initialWeight' => $report->weight->initialWeight,
                'finalWeight' => $report->weight->finalWeight,
                'change' => $report->weight->change,

                'entries' => array_map(
                    static fn ($entry) => [
                        'measuredAt' => $entry->measuredAt->format('Y-m-d'),
                        'weight' => $entry->weight,
                        'note' => $entry->note,
                    ],
                    $report->weight->entries,
                ),
            ],

            'hydration' => [
                'totalQuantity' => $report->hydration->totalQuantity,
                'averageDailyQuantity' => $report->hydration->averageDailyQuantity,
                'daysWithEntries' => $report->hydration->daysWithEntries,
                'totalDays' => $report->hydration->totalDays,

                'entries' => array_map(
                    static fn ($entry) => [
                        'date' => $entry->date->format('Y-m-d'),
                        'drinkType' => $entry->drinkType->value,
                        'quantity' => $entry->quantity,
                        'description' => $entry->description,
                        'note' => $entry->note,
                    ],
                    $report->hydration->entries,
                ),
            ],

            'food' => [
                'entryCount' => $report->food->entryCount,
                'averageHungerLevel' => $report->food->averageHungerLevel,
                'averagePleasureLevel' => $report->food->averagePleasureLevel,
                'mealTypeCounts' => $report->food->mealTypeCounts,

                'entries' => array_map(
                    static fn ($entry) => [
                        'date' => $entry->date->format('Y-m-d H:i'),
                        'mealType' => $entry->mealType,
                        'description' => $entry->description,
                        'hungerLevel' => $entry->hungerLevel,
                        'pleasureLevel' => $entry->pleasureLevel,
                        'cause' => $entry->cause,
                        'note' => $entry->note,
                    ],
                    $report->food->entries,
                ),
            ],

            'checkin' => [
                'totalDays' => $report->checkin->totalDays,
                'daysWithEntries' => $report->checkin->daysWithEntries,
                'averageMoodLevel' => $report->checkin->averageMoodLevel,
                'averageEnergyLevel' => $report->checkin->averageEnergyLevel,
                'averageFrustrationLevel' => $report->checkin->averageFrustrationLevel,
                'averagePainLevel' => $report->checkin->averagePainLevel,

                'entries' => array_map(
                    static fn ($entry) => [
                        'date' => $entry->date->format('Y-m-d'),
                        'moodLevel' => $entry->moodLevel,
                        'energyLevel' => $entry->energyLevel,
                        'frustrationLevel' => $entry->frustrationLevel,
                        'painLevel' => $entry->painLevel,
                        'note' => $entry->note,
                    ],
                    $report->checkin->entries,
                ),
            ],

            'sleep' => [
                'totalDays' => $report->sleep->totalDays,
                'nightsWithEntries' => $report->sleep->nightsWithEntries,
                'averageDurationInMinutes' => $report->sleep->averageDurationInMinutes,
                'averageQuality' => $report->sleep->averageQuality,
                'shortestDurationInMinutes' => $report->sleep->shortestDurationInMinutes,
                'longestDurationInMinutes' => $report->sleep->longestDurationInMinutes,

                'entries' => array_map(
                    static fn ($entry) => [
                        'date' => $entry->date->format('Y-m-d'),
                        'bedTime' => $entry->bedTime->format('Y-m-d H:i'),
                        'wakeUpTime' => $entry->wakeUpTime->format('Y-m-d H:i'),
                        'durationInMinutes' => $entry->durationInMinutes,
                        'quality' => $entry->quality,
                        'note' => $entry->note,
                    ],
                    $report->sleep->entries,
                ),
            ],

            'activity' => [
                'entryCount' => $report->activity->entryCount,
                'averagePainLevel' => $report->activity->averagePainLevel,

                'entries' => array_map(
                    static fn ($entry) => [
                        'date' => $entry->date->format('Y-m-d'),
                        'description' => $entry->description,
                        'painLevel' => $entry->painLevel,
                        'note' => $entry->note,
                    ],
                    $report->activity->entries,
                ),
            ],

            'milestone' => [
                'totalCount' => $report->milestone->totalCount,
                'achievedCount' => $report->milestone->achievedCount,

                'entries' => array_map(
                    static fn ($entry) => [
                        'title' => $entry->title,
                        'description' => $entry->description,
                        'type' => $entry->type,
                        'targetValue' => $entry->targetValue,
                        'achievedAt' => $entry->achievedAt?->format('Y-m-d'),
                    ],
                    $report->milestone->entries,
                ),
            ],
        ];

        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }
}