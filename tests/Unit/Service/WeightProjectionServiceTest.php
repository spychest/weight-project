<?php

namespace App\Tests\Unit\Service;

use App\Entity\Milestone;
use App\Entity\Profile;
use App\Entity\WeightEntry;
use App\Service\Milestone\MilestoneService;
use App\Service\Weight\WeightProjectionService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WeightProjectionServiceTest extends TestCase
{
    private WeightProjectionService $weightProjectionService;

    protected function setUp(): void
    {
        $this->weightProjectionService = new WeightProjectionService(new MilestoneService());
    }

    #[Test]
    public function itProjectsTheFinalTargetAndEveryPendingWeightMilestoneFromTheObservedTrend(): void
    {
        $profile = $this->createProfile(targetWeight: 90);
        $profile
            ->addMilestone($this->createMilestone('Premier palier', 96))
            ->addMilestone($this->createMilestone('Deuxième palier', 95))
            ->addMilestone($this->createMilestone('Palier déjà atteint', 98, new \DateTimeImmutable('2026-01-15')))
            ->addMilestone($this->createMilestone('Objectif non lié au poids', 94, null, 'ACTIVITY'));

        $projection = $this->weightProjectionService->project($profile, [
            $this->createWeightEntry(100, '2026-01-01'),
            $this->createWeightEntry(99, '2026-01-08'),
            $this->createWeightEntry(98, '2026-01-15'),
            $this->createWeightEntry(97, '2026-01-22'),
        ]);

        self::assertTrue($projection->isAvailable());
        self::assertSame(4, $projection->measurementCount);
        self::assertSame(21, $projection->observationPeriodInDays);
        self::assertSame(-1.0, $projection->weeklyWeightChange);
        self::assertCount(3, $projection->goals);

        self::assertTrue($projection->goals[0]->isFinalTarget);
        self::assertSame('Poids objectif', $projection->goals[0]->label);
        self::assertSame('2026-03-12', $projection->goals[0]->projectedDate->format('Y-m-d'));
        self::assertSame('Premier palier', $projection->goals[1]->label);
        self::assertSame('2026-01-29', $projection->goals[1]->projectedDate->format('Y-m-d'));
        self::assertSame('Deuxième palier', $projection->goals[2]->label);
        self::assertSame('2026-02-05', $projection->goals[2]->projectedDate->format('Y-m-d'));
    }

    #[Test]
    public function itExplainsWhenTooFewMeasurementsAreAvailable(): void
    {
        $projection = $this->weightProjectionService->project($this->createProfile(), [
            $this->createWeightEntry(100, '2026-01-01'),
            $this->createWeightEntry(99, '2026-01-08'),
        ]);

        self::assertFalse($projection->isAvailable());
        self::assertSame('Au moins 3 pesées sont nécessaires pour calculer une projection indicative.', $projection->unavailableReason);
    }

    #[Test]
    public function itDoesNotProjectAResultWhenTheRecentTrendIsNotDecreasing(): void
    {
        $projection = $this->weightProjectionService->project($this->createProfile(), [
            $this->createWeightEntry(98, '2026-01-01'),
            $this->createWeightEntry(99, '2026-01-08'),
            $this->createWeightEntry(100, '2026-01-15'),
        ]);

        self::assertFalse($projection->isAvailable());
        self::assertSame(
            'La tendance récente ne descend pas suffisamment pour projeter une date vers les objectifs.',
            $projection->unavailableReason,
        );
    }

    private function createProfile(float $targetWeight = 90): Profile
    {
        return (new Profile())
            ->setStartingWeight(105)
            ->setTargetWeight($targetWeight);
    }

    private function createWeightEntry(float $weight, string $measurementDate): WeightEntry
    {
        return (new WeightEntry())
            ->setWeight($weight)
            ->setMeasuredAt(new \DateTimeImmutable($measurementDate));
    }

    private function createMilestone(
        string $title,
        float $targetWeight,
        ?\DateTimeImmutable $achievedAt = null,
        string $type = 'POIDS',
    ): Milestone {
        return (new Milestone())
            ->setTitle($title)
            ->setType($type)
            ->setTargetValue($targetWeight)
            ->setAchievedAt($achievedAt);
    }
}
