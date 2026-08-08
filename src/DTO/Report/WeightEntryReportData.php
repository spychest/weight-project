<?php

namespace App\DTO\Report;

final readonly class WeightEntryReportData
{
    public function __construct(
        public \DateTimeImmutable $measuredAt,
        public float $weight,
        public ?string $note,
    ) {
    }
}