<?php

namespace App\DTO\Report;

final readonly class ActivityEntryReportData
{
    public function __construct(
        public \DateTimeImmutable $date,
        public string $description,
        public ?int $painLevel,
        public ?string $note,
    ) {
    }
}