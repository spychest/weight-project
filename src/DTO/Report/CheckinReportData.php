<?php

namespace App\DTO\Report;

final readonly class CheckinReportData
{
    public function __construct(
        public int $totalDays,
        public int $daysWithEntries,
        public ?float $averageMoodLevel,
        public ?float $averageEnergyLevel,
        public ?float $averageFrustrationLevel,
        public ?float $averagePainLevel,

        /** @var CheckinEntryReportData[] */
        public array $entries,
    ) {
    }
}