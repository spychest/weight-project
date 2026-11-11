<?php

namespace App\Tests\Unit\Service;

use App\DTO\Report\FoodReportData;
use App\Entity\Profile;
use App\Entity\WeightEntry;
use App\Repository\WeightEntryRepository;
use App\Service\Report\MedicalWeightReportService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class MedicalWeightReportServiceTest extends TestCase
{
    private WeightEntryRepository&Stub $weightEntryRepository;
    private MedicalWeightReportService $service;

    protected function setUp(): void
    {
        $this->weightEntryRepository = $this->createStub(WeightEntryRepository::class);
        $this->service = new MedicalWeightReportService($this->weightEntryRepository);
    }

    #[Test]
    public function itUsesTheDeclaredStartingWeightInsteadOfTheFirstMeasurement(): void
    {
        $profile = $this->createProfile(100.0, '2026-01-01');
        $this->weightEntryRepository->method('findAllForProfile')->willReturn([
            $this->createWeightEntry(98.0, '2026-01-15'),
            $this->createWeightEntry(95.0, '2026-02-01'),
        ]);

        $report = $this->service->generate(
            $profile,
            $this->createFoodReport(),
            'Moins de boissons sucrées.',
            'Question à poser au médecin.',
            new \DateTimeImmutable('2026-02-02'),
        );

        self::assertSame(100.0, $report->startingWeight);
        self::assertSame(95.0, $report->latestWeight);
        self::assertSame(-5.0, $report->totalChange);
        self::assertSame(-5.0, $report->totalChangePercentage);
        self::assertSame(31, $report->trackingDurationDays);
        self::assertEqualsWithDelta(-1.129, $report->averageWeeklyChange, 0.001);
        self::assertSame(-3.0, $report->previousMeasurementChange);
        self::assertSame(2, $report->measurementCount);
        self::assertFalse($report->latestWeightIsDeclared);
        self::assertTrue($report->hasGeneralTrend);
        self::assertStringStartsWith('data:image/svg+xml;base64,', $report->chartDataUri);
    }

    #[Test]
    public function itKeepsUnavailableIndicatorsExplicitWhenThereIsNoFollowUpMeasurement(): void
    {
        $profile = $this->createProfile(100.0, '2026-01-01');
        $this->weightEntryRepository->method('findAllForProfile')->willReturn([]);

        $report = $this->service->generate(
            $profile,
            $this->createFoodReport(),
            null,
            null,
            new \DateTimeImmutable('2026-02-02'),
        );

        self::assertSame(100.0, $report->latestWeight);
        self::assertTrue($report->latestWeightIsDeclared);
        self::assertNull($report->totalChange);
        self::assertNull($report->totalChangePercentage);
        self::assertNull($report->averageWeeklyChange);
        self::assertNull($report->trackingDurationDays);
        self::assertNull($report->thirtyDayChange);
        self::assertFalse($report->hasGeneralTrend);
        self::assertFalse($report->hasRecentTrend);
    }

    #[Test]
    public function itCalculatesRecentIndicatorsOnlyWhenTheDataSupportsThem(): void
    {
        $profile = $this->createProfile(100.0, '2025-12-01');
        $this->weightEntryRepository->method('findAllForProfile')->willReturn([
            $this->createWeightEntry(98.0, '2026-01-01'),
            $this->createWeightEntry(97.0, '2026-01-15'),
            $this->createWeightEntry(96.0, '2026-01-25'),
            $this->createWeightEntry(95.0, '2026-01-31'),
        ]);

        $report = $this->service->generate(
            $profile,
            $this->createFoodReport(),
            null,
            null,
            new \DateTimeImmutable('2026-02-01'),
        );

        self::assertSame(-3.0, $report->thirtyDayChange);
        self::assertTrue($report->hasRecentTrend);
    }

    #[Test]
    public function itDoesNotInventARecentTrendFromTooFewDistinctDates(): void
    {
        $profile = $this->createProfile(100.0, '2026-01-01');
        $this->weightEntryRepository->method('findAllForProfile')->willReturn([
            $this->createWeightEntry(99.0, '2026-01-20 08:00'),
            $this->createWeightEntry(98.8, '2026-01-20 18:00'),
            $this->createWeightEntry(98.0, '2026-01-31'),
        ]);

        $report = $this->service->generate(
            $profile,
            $this->createFoodReport(),
            null,
            null,
            new \DateTimeImmutable('2026-02-01'),
        );

        self::assertFalse($report->hasRecentTrend);
    }

    #[Test]
    public function itMergesAndIdentifiesAnEquivalentInitialMeasurement(): void
    {
        $profile = $this->createProfile(100.0, '2026-01-01');
        $this->weightEntryRepository->method('findAllForProfile')->willReturn([
            $this->createWeightEntry(100.0, '2026-01-01 08:00'),
            $this->createWeightEntry(98.0, '2026-01-20'),
        ]);

        $report = $this->service->generate(
            $profile,
            $this->createFoodReport(),
            null,
            null,
            new \DateTimeImmutable('2026-02-01'),
        );
        $chart = base64_decode(
            substr($report->chartDataUri, strlen('data:image/svg+xml;base64,')),
            true,
        );

        self::assertIsString($chart);
        self::assertSame(1, substr_count($chart, 'transform="rotate(45'));
        self::assertSame(2, $report->measurementCount);
    }

    private function createProfile(float $startingWeight, string $createdAt): Profile
    {
        return (new Profile())
            ->setStartingWeight($startingWeight)
            ->setCreatedAt(new \DateTimeImmutable($createdAt))
            ->setBirthDate(new \DateTimeImmutable('1990-01-01'))
            ->setHeight(175.0);
    }

    private function createWeightEntry(float $weight, string $measuredAt): WeightEntry
    {
        return (new WeightEntry())
            ->setWeight($weight)
            ->setMeasuredAt(new \DateTimeImmutable($measuredAt));
    }

    private function createFoodReport(): FoodReportData
    {
        return new FoodReportData(
            entryCount: 4,
            averageHungerLevel: 4.5,
            averagePleasureLevel: 7.0,
            mealTypeCounts: [],
            entries: [],
        );
    }
}
