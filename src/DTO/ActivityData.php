<?php

namespace App\DTO;

final readonly class ActivityData
{
    public function __construct(
        public \DateTimeImmutable $date,
        public string $description,
        public ?int $painLevel,
        public ?string $note,
    )
    {
    }
}