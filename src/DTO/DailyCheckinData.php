<?php

namespace App\DTO;

final readonly class DailyCheckinData
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