<?php

namespace App\DTO;

final readonly class SleepEntryData
{
    public function __construct(
        public \DateTimeImmutable $date,
        public \DateTimeImmutable $bedTime,
        public \DateTimeImmutable $wakeUpTime,
        public int $quality,
        public ?string $note,
        public int $durationMinutes,
    ) {
    }

    public function getDurationHours(): int
    {
        return intdiv($this->durationMinutes, 60);
    }

    public function getDurationRemainingMinutes(): int
    {
        return $this->durationMinutes % 60;
    }
}