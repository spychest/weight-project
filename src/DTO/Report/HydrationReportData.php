<?php

namespace App\DTO\Report;

final readonly class HydrationReportData
{
    public function __construct(
        public int $totalQuantity,
        public float $averageDailyQuantity,
        public int $daysWithEntries,
        public int $totalDays,

        /** @var DrinkEntryReportData[] */
        public array $entries,
    ) {
    }
}