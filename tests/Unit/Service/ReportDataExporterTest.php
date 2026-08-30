<?php

namespace App\Tests\Unit\Service;

use App\DTO\Report\ActivityReportData;
use App\DTO\Report\CheckinReportData;
use App\DTO\Report\FoodReportData;
use App\DTO\Report\HydrationReportData;
use App\DTO\Report\MilestoneReportData;
use App\DTO\Report\PeriodReportData;
use App\DTO\Report\SleepReportData;
use App\DTO\Report\WeightEntryReportData;
use App\DTO\Report\WeightReportData;
use App\Service\Report\ReportDataExporter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReportDataExporterTest extends TestCase
{
    #[Test]
    public function itExportsAReadableUnicodeJsonDocument(): void
    {
        $report = new PeriodReportData(
            startDate: new \DateTimeImmutable('2026-08-01'),
            endDate: new \DateTimeImmutable('2026-08-31'),
            weight: new WeightReportData(
                entryCount: 1,
                initialWeight: 99.5,
                finalWeight: 99.5,
                change: 0.0,
                entries: [new WeightEntryReportData(
                    measuredAt: new \DateTimeImmutable('2026-08-15'),
                    weight: 99.5,
                    note: 'Très bonne progression',
                )],
            ),
            hydration: new HydrationReportData(0, 0.0, 0, 31, []),
            food: new FoodReportData(0, null, null, [], []),
            checkin: new CheckinReportData(31, 0, null, null, null, null, []),
            sleep: new SleepReportData(31, 0, null, null, null, null, []),
            activity: new ActivityReportData(0, null, []),
            milestone: new MilestoneReportData(0, 0, []),
        );

        $serializedReport = (new ReportDataExporter())->export($report);
        $decodedReport = json_decode($serializedReport, true, flags: JSON_THROW_ON_ERROR);

        self::assertStringContainsString("\n", $serializedReport);
        self::assertStringContainsString('Très bonne progression', $serializedReport);
        self::assertSame('2026-08-01', $decodedReport['period']['startDate']);
        self::assertSame(99.5, $decodedReport['weight']['entries'][0]['weight']);
        self::assertSame(31, $decodedReport['hydration']['totalDays']);
    }
}
