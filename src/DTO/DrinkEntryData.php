<?php

namespace App\DTO;

use App\Enum\DrinkType;

final readonly class DrinkEntryData
{
    public function __construct(
        public DrinkType $drinkType,
        public int $quantity,
        public \DateTimeImmutable $date,
        public ?string $description,
    ) {
    }
}