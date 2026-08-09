<?php

namespace App\DTO\Report;

final readonly class ActivityReportData
{
    public function __construct(
        public int $entryCount,
        public ?float $averagePainLevel,

        /** @var ActivityEntryReportData[] */
        public array $entries,
    ) {
    }
}