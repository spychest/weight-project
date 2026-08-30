<?php

namespace App\Tests\Unit\Service;

use App\Entity\DailyCheckin;
use App\Entity\DrinkEntry;
use App\Entity\FoodEvent;
use App\Entity\Milestone;
use App\Entity\Profile;
use App\Entity\SleepEntry;
use App\Entity\WeightEntry;
use App\Enum\MealType;
use App\Repository\DailyCheckinRepository;
use App\Repository\DrinkEntryRepository;
use App\Repository\FoodEventRepository;
use App\Repository\MilestoneRepository;
use App\Repository\SleepEntryRepository;
use App\Repository\WeightEntryRepository;
use App\Service\GraphService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class GraphServiceTest extends TestCase
{
    private WeightEntryRepository&Stub $weightEntryRepository;
    private MilestoneRepository&Stub $milestoneRepository;
    private DrinkEntryRepository&Stub $drinkEntryRepository;
    private SleepEntryRepository&Stub $sleepEntryRepository;
    private FoodEventRepository&Stub $foodEventRepository;
    private DailyCheckinRepository&Stub $dailyCheckinRepository;
    private GraphService $graphService;

    protected function setUp(): void
    {
        $this->weightEntryRepository = $this->createStub(WeightEntryRepository::class);
        $this->milestoneRepository = $this->createStub(MilestoneRepository::class);
        $this->drinkEntryRepository = $this->createStub(DrinkEntryRepository::class);
        $this->sleepEntryRepository = $this->createStub(SleepEntryRepository::class);
        $this->foodEventRepository = $this->createStub(FoodEventRepository::class);
        $this->dailyCheckinRepository = $this->createStub(DailyCheckinRepository::class);

        $this->graphService = new GraphService(
            $this->weightEntryRepository,
            $this->milestoneRepository,
            $this->drinkEntryRepository,
            $this->sleepEntryRepository,
            $this->foodEventRepository,
            $this->dailyCheckinRepository,
        );
    }

    #[Test]
    public function itBuildsWeightGraphDataWithTrendsAndTheClosestMilestone(): void
    {
        $profile = (new Profile())->setStartingWeight(100.0);
        $weightEntries = [
            $this->createWeightEntry('2026-07-01', 100.0),
            $this->createWeightEntry('2026-07-08', 99.0),
            $this->createWeightEntry('2026-07-15', 98.0),
        ];
        $milestones = [
            $this->createMilestone('Objectif 95', 95.0),
            $this->createMilestone('Objectif 90', 90.0),
        ];

        $this->weightEntryRepository->method('findAllForProfile')->willReturn($weightEntries);
        $this->milestoneRepository->method('findForProfile')->willReturn($milestones);

        $graphData = $this->graphService->getWeightGraphData($profile);

        self::assertSame(['01/07/2026', '08/07/2026', '15/07/2026'], $graphData['labels']);
        self::assertSame([100.0, 99.0, 98.0], $graphData['weights']);
        self::assertSame(95.0, $graphData['milestone']);
        self::assertSame('Objectif 95', $graphData['milestoneTitle']);
        self::assertSame(-1.0, $graphData['trends']['all']);
    }

    #[Test]
    public function itUsesStartingWeightWhenNoWeightEntryExists(): void
    {
        $profile = (new Profile())->setStartingWeight(110.0);
        $this->weightEntryRepository->method('findAllForProfile')->willReturn([]);
        $this->milestoneRepository->method('findForProfile')->willReturn([$this->createMilestone('Objectif', 100.0)]);

        $graphData = $this->graphService->getWeightGraphData($profile);

        self::assertSame([], $graphData['weights']);
        self::assertSame(100.0, $graphData['milestone']);
        self::assertNull($graphData['trends']['all']);
    }

    #[Test]
    public function itCalculatesSleepDurationAcrossMidnight(): void
    {
        $profile = new Profile();
        $sleepEntry = (new SleepEntry())
            ->setDate(new \DateTimeImmutable('2026-08-30'))
            ->setBedTime(new \DateTimeImmutable('22:30'))
            ->setWakeUpTime(new \DateTimeImmutable('06:15'))
            ->setQuality(8);
        $this->sleepEntryRepository->method('findAllForProfile')->willReturn([$sleepEntry]);

        self::assertSame(
            [['date' => '2026-08-30', 'duration' => 7.75]],
            $this->graphService->getSleepDetailData($profile),
        );
    }

    #[Test]
    public function itGroupsMealsByTypeAndIgnoresEntriesWithoutAType(): void
    {
        $profile = new Profile();
        $lunch = (new FoodEvent())->setMealType(MealType::LUNCH);
        $dinner = (new FoodEvent())->setMealType(MealType::DINNER);
        $withoutType = new FoodEvent();
        $this->foodEventRepository->method('findAllForProfile')->willReturn([$lunch, $lunch, $dinner, $withoutType]);

        $graphData = $this->graphService->getMealTypeGraphData($profile);

        self::assertSame(['lunch', 'dinner'], $graphData['labels']);
        self::assertSame([2, 1], $graphData['values']);
    }

    #[Test]
    public function itReturnsExplicitDailyCheckinSeries(): void
    {
        $profile = new Profile();
        $dailyCheckin = (new DailyCheckin())
            ->setDate(new \DateTimeImmutable('2026-08-30'))
            ->setMoodLevel(8)
            ->setEnergyLevel(7)
            ->setFrustrationLevel(3)
            ->setPainLevel(2);
        $this->dailyCheckinRepository->method('findAllForProfile')->willReturn([$dailyCheckin]);

        $graphData = $this->graphService->getDailyCheckinGraphData($profile);

        self::assertSame(['30/08/2026'], $graphData['labels']);
        self::assertSame([8], $graphData['mood']);
        self::assertSame([7], $graphData['energy']);
        self::assertSame([3], $graphData['frustration']);
        self::assertSame([2], $graphData['pain']);
    }

    #[Test]
    public function itReturnsHydrationDetailsWithoutChangingRecordedQuantities(): void
    {
        $profile = new Profile();
        $drinkEntry = (new DrinkEntry())
            ->setDate(new \DateTimeImmutable('2026-08-30'))
            ->setQuantity(750);
        $this->drinkEntryRepository->method('findAllForProfile')->willReturn([$drinkEntry]);

        self::assertSame(
            [['date' => '2026-08-30', 'quantity' => 750]],
            $this->graphService->getHydrationDetailData($profile),
        );
    }

    private function createWeightEntry(string $date, float $weight): WeightEntry
    {
        return (new WeightEntry())
            ->setMeasuredAt(new \DateTimeImmutable($date))
            ->setWeight($weight);
    }

    private function createMilestone(string $title, float $targetWeight): Milestone
    {
        return (new Milestone())
            ->setTitle($title)
            ->setType('weight')
            ->setTargetValue($targetWeight);
    }
}
