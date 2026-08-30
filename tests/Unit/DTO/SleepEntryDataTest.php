<?php

namespace App\Tests\Unit\DTO;

use App\DTO\SleepEntryData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SleepEntryDataTest extends TestCase
{
    #[Test]
    public function itSeparatesACompleteSleepDurationIntoHoursAndMinutes(): void
    {
        $sleepEntryData = new SleepEntryData(
            date: new \DateTimeImmutable('2026-08-30'),
            bedTime: new \DateTimeImmutable('2026-08-30 22:30'),
            wakeUpTime: new \DateTimeImmutable('2026-08-31 06:15'),
            quality: 8,
            note: null,
            durationMinutes: 465,
        );

        self::assertSame(7, $sleepEntryData->getDurationHours());
        self::assertSame(45, $sleepEntryData->getDurationRemainingMinutes());
    }
}
