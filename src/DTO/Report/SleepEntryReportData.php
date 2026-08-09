<?php

namespace App\DTO\Report;

final readonly class SleepEntryReportData
{
    public function __construct(
        public \DateTimeImmutable $date,
        public \DateTimeImmutable $bedTime,
        public \DateTimeImmutable $wakeUpTime,
        public int $durationInMinutes,
        public int $quality,
        public ?string $note,
    ) {
    }
}