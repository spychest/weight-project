<?php

namespace App\DTO\Report;

final readonly class MilestoneReportData
{
    public function __construct(
        public int $totalCount,
        public int $achievedCount,

        /** @var MilestoneEntryReportData[] */
        public array $entries,
    ) {
    }
}