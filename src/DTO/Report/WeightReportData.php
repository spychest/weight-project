<?php

namespace App\DTO\Report;

final readonly class WeightReportData
{
    public function __construct(
        public int $entryCount,
        public ?float $initialWeight,
        public ?float $finalWeight,
        public ?float $change,

        /** @var WeightEntryReportData[] */
        public array $entries,
    ) {
    }
}