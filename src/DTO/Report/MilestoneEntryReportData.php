<?php

namespace App\DTO\Report;

final readonly class MilestoneEntryReportData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public string $type,
        public float $targetValue,
        public ?\DateTimeImmutable $achievedAt,
    ) {
    }
}