<?php

namespace App\Tests\Unit\Service;

use App\Entity\Activity;
use App\Entity\DailyCheckin;
use App\Entity\DrinkEntry;
use App\Entity\FoodEvent;
use App\Entity\Milestone;
use App\Entity\Profile;
use App\Entity\SleepEntry;
use App\Entity\WeightEntry;
use App\Enum\DrinkType;
use App\Enum\MealType;
use App\Repository\ActivityRepository;
use App\Repository\DailyCheckinRepository;
use App\Repository\DrinkEntryRepository;
use App\Repository\FoodEventRepository;
use App\Repository\MilestoneRepository;
use App\Repository\SleepEntryRepository;
use App\Repository\WeightEntryRepository;
use App\Service\Report\PeriodReportService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class PeriodReportServiceTest extends TestCase
{
    private WeightEntryRepository&Stub $weightEntryRepository;
    private DrinkEntryRepository&Stub $drinkEntryRepository;
    private FoodEventRepository&Stub $foodEventRepository;
    private DailyCheckinRepository&Stub $dailyCheckinRepository;
    private SleepEntryRepository&Stub $sleepEntryRepository;
    private ActivityRepository&Stub $activityRepository;
    private MilestoneRepository&Stub $milestoneRepository;
    private PeriodReportService $periodReportService;

    protected function setUp(): void
    {
        $this->weightEntryRepository = $this->createStub(WeightEntryRepository::class);
        $this->drinkEntryRepository = $this->createStub(DrinkEntryRepository::class);
        $this->foodEventRepository = $this->createStub(FoodEventRepository::class);
        $this->dailyCheckinRepository = $this->createStub(DailyCheckinRepository::class);
        $this->sleepEntryRepository = $this->createStub(SleepEntryRepository::class);
        $this->activityRepository = $this->createStub(ActivityRepository::class);
        $this->milestoneRepository = $this->createStub(MilestoneRepository::class);

        $this->periodReportService = new PeriodReportService(
            $this->weightEntryRepository,
            $this->drinkEntryRepository,
            $this->foodEventRepository,
            $this->dailyCheckinRepository,
            $this->sleepEntryRepository,
            $this->activityRepository,
            $this->milestoneRepository,
        );
    }

    #[Test]
    public function itCalculatesEverySectionOfAPeriodReport(): void
    {
        $profile = new Profile();
        $startDate = new \DateTimeImmutable('2026-08-01');
        $endDate = new \DateTimeImmutable('2026-08-03');

        $this->weightEntryRepository->method('findForPeriod')->willReturn([
            (new WeightEntry())->setMeasuredAt($startDate)->setWeight(100.0),
            (new WeightEntry())->setMeasuredAt($endDate)->setWeight(98.5),
        ]);
        $this->drinkEntryRepository->method('findForPeriod')->willReturn([
            $this->createDrinkEntry('2026-08-01', 1000),
            $this->createDrinkEntry('2026-08-01', 500),
            $this->createDrinkEntry('2026-08-03', 900),
        ]);
        $this->foodEventRepository->method('findForPeriod')->willReturn([
            $this->createFoodEvent(MealType::LUNCH, 4, 8),
            $this->createFoodEvent(MealType::DINNER, null, 6),
        ]);
        $this->dailyCheckinRepository->method('findForPeriod')->willReturn([
            $this->createDailyCheckin('2026-08-01', 8, 6, 2, 4),
            $this->createDailyCheckin('2026-08-02', 6, 8, 4, null),
        ]);
        $this->sleepEntryRepository->method('findForPeriod')->willReturn([
            $this->createSleepEntry('2026-08-01', '22:30', '06:30', 8),
            $this->createSleepEntry('2026-08-02', '23:00', '06:00', 6),
        ]);
        $this->activityRepository->method('findForPeriod')->willReturn([
            $this->createActivity('Marche', 2),
            $this->createActivity('Étirements', null),
        ]);
        $this->milestoneRepository->method('findForProfile')->willReturn([
            $this->createMilestone('Atteint pendant la période', new \DateTimeImmutable('2026-08-02')),
            $this->createMilestone('Non atteint', null),
        ]);

        $report = $this->periodReportService->generate($profile, $startDate, $endDate);

        self::assertSame(2, $report->weight->entryCount);
        self::assertSame(100.0, $report->weight->initialWeight);
        self::assertSame(98.5, $report->weight->finalWeight);
        self::assertSame(-1.5, $report->weight->change);

        self::assertSame(2400, $report->hydration->totalQuantity);
        self::assertSame(800.0, $report->hydration->averageDailyQuantity);
        self::assertSame(2, $report->hydration->daysWithEntries);
        self::assertSame(3, $report->hydration->totalDays);

        self::assertSame(4.0, $report->food->averageHungerLevel);
        self::assertSame(7.0, $report->food->averagePleasureLevel);
        self::assertSame(['lunch' => 1, 'dinner' => 1], $report->food->mealTypeCounts);

        self::assertSame(7.0, $report->checkin->averageMoodLevel);
        self::assertSame(7.0, $report->checkin->averageEnergyLevel);
        self::assertSame(3.0, $report->checkin->averageFrustrationLevel);
        self::assertSame(4.0, $report->checkin->averagePainLevel);

        self::assertSame(450, $report->sleep->averageDurationInMinutes);
        self::assertSame(7.0, $report->sleep->averageQuality);
        self::assertSame(420, $report->sleep->shortestDurationInMinutes);
        self::assertSame(480, $report->sleep->longestDurationInMinutes);

        self::assertSame(2, $report->activity->entryCount);
        self::assertSame(2.0, $report->activity->averagePainLevel);
        self::assertSame(2, $report->milestone->totalCount);
        self::assertSame(1, $report->milestone->achievedCount);
    }

    #[Test]
    public function itReturnsNeutralValuesWhenThePeriodContainsNoEntry(): void
    {
        $this->milestoneRepository->method('findForProfile')->willReturn([]);

        $report = $this->periodReportService->generate(
            new Profile(),
            new \DateTimeImmutable('2026-08-01'),
            new \DateTimeImmutable('2026-08-03'),
        );

        self::assertSame(0, $report->weight->entryCount);
        self::assertNull($report->weight->change);
        self::assertSame(0, $report->hydration->totalQuantity);
        self::assertSame(0.0, $report->hydration->averageDailyQuantity);
        self::assertNull($report->food->averageHungerLevel);
        self::assertNull($report->checkin->averageMoodLevel);
        self::assertNull($report->sleep->averageDurationInMinutes);
        self::assertNull($report->activity->averagePainLevel);
        self::assertSame(0, $report->milestone->totalCount);
    }

    private function createDrinkEntry(string $date, int $quantity): DrinkEntry
    {
        return (new DrinkEntry())
            ->setDate(new \DateTimeImmutable($date))
            ->setDrinkType(DrinkType::WATER)
            ->setQuantity($quantity);
    }

    private function createFoodEvent(MealType $mealType, ?int $hungerLevel, int $pleasureLevel): FoodEvent
    {
        return (new FoodEvent())
            ->setEatenAt(new \DateTimeImmutable('2026-08-01 12:00'))
            ->setMealType($mealType)
            ->setDescription('Repas de test')
            ->setHungerLevel($hungerLevel)
            ->setPleasureLevel($pleasureLevel);
    }

    private function createDailyCheckin(
        string $date,
        int $moodLevel,
        int $energyLevel,
        int $frustrationLevel,
        ?int $painLevel,
    ): DailyCheckin {
        return (new DailyCheckin())
            ->setDate(new \DateTimeImmutable($date))
            ->setMoodLevel($moodLevel)
            ->setEnergyLevel($energyLevel)
            ->setFrustrationLevel($frustrationLevel)
            ->setPainLevel($painLevel);
    }

    private function createSleepEntry(string $date, string $bedTime, string $wakeUpTime, int $quality): SleepEntry
    {
        return (new SleepEntry())
            ->setDate(new \DateTimeImmutable($date))
            ->setBedTime(new \DateTimeImmutable($bedTime))
            ->setWakeUpTime(new \DateTimeImmutable($wakeUpTime))
            ->setQuality($quality);
    }

    private function createActivity(string $description, ?int $painLevel): Activity
    {
        return (new Activity())
            ->setDate(new \DateTimeImmutable('2026-08-01'))
            ->setDescription($description)
            ->setPainLevel($painLevel);
    }

    private function createMilestone(string $title, ?\DateTimeImmutable $achievedAt): Milestone
    {
        return (new Milestone())
            ->setTitle($title)
            ->setType('weight')
            ->setTargetValue(95.0)
            ->setAchievedAt($achievedAt);
    }
}
