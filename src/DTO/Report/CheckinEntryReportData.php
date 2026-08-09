<?php

namespace App\DTO\Report;

final readonly class CheckinEntryReportData
{
    public function __construct(
        public \DateTimeImmutable $date,
        public int $moodLevel,
        public int $energyLevel,
        public int $frustrationLevel,
        public ?int $painLevel,
        public ?string $note,
    ) {
    }
}