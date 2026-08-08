<?php

namespace App\DTO\Report;

use App\Enum\DrinkType;

final readonly class DrinkEntryReportData
{
    public function __construct(
        public \DateTimeImmutable $date,
        public DrinkType $drinkType,
        public int $quantity,
        public ?string $description,
        public ?string $note,
    ) {
    }
}