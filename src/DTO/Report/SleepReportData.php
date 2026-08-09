<?php

namespace App\DTO\Report;

final readonly class SleepReportData
{
    public function __construct(
        public int $totalDays,
        public int $nightsWithEntries,
        public ?int $averageDurationInMinutes,
        public ?float $averageQuality,
        public ?int $shortestDurationInMinutes,
        public ?int $longestDurationInMinutes,

        /** @var SleepEntryReportData[] */
        public array $entries,
    ) {
    }
}