<?php

namespace App\Tests\Unit\Service;

use App\Entity\Milestone;
use App\Entity\Profile;
use App\Service\Milestone\MilestoneService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MilestoneServiceTest extends TestCase
{
    private MilestoneService $milestoneService;

    protected function setUp(): void
    {
        $this->milestoneService = new MilestoneService();
    }

    #[Test]
    public function itReturnsTheClosestMilestoneBelowTheCurrentWeight(): void
    {
        $profile = new Profile();
        $profile->addMilestone($this->createWeightMilestone('Premier objectif', 95.0));
        $profile->addMilestone($this->createWeightMilestone('Objectif suivant', 90.0));
        $profile->addMilestone($this->createWeightMilestone('Objectif déjà dépassé', 105.0));

        $nextMilestone = $this->milestoneService->findNextMilestone($profile, 100.0);

        self::assertNotNull($nextMilestone);
        self::assertSame('Premier objectif', $nextMilestone->getTitle());
        self::assertSame(95.0, $nextMilestone->getTargetValue());
    }

    #[Test]
    public function itIgnoresAMilestoneEqualToTheCurrentWeight(): void
    {
        $profile = new Profile();
        $profile->addMilestone($this->createWeightMilestone('Poids actuel', 95.0));
        $profile->addMilestone($this->createWeightMilestone('Prochain objectif', 90.0));

        $nextMilestone = $this->milestoneService->findNextMilestone($profile, 95.0);

        self::assertNotNull($nextMilestone);
        self::assertSame('Prochain objectif', $nextMilestone->getTitle());
    }

    #[Test]
    public function itReturnsNullWhenNoMilestoneIsBelowTheCurrentWeight(): void
    {
        $profile = new Profile();
        $profile->addMilestone($this->createWeightMilestone('Objectif dépassé', 100.0));

        self::assertNull($this->milestoneService->findNextMilestone($profile, 95.0));
    }

    #[Test]
    public function itDoesNotReturnAnAlreadyValidatedMilestoneAfterWeightRegain(): void
    {
        $profile = new Profile();
        $profile->addMilestone(
            $this->createWeightMilestone('Déjà atteint', 95.0)
                ->setAchievedAt(new \DateTimeImmutable('2026-08-01')),
        );
        $profile->addMilestone($this->createWeightMilestone('Prochain objectif', 90.0));

        $nextMilestone = $this->milestoneService->findNextMilestone($profile, 98.0);

        self::assertNotNull($nextMilestone);
        self::assertSame('Prochain objectif', $nextMilestone->getTitle());
    }

    #[Test]
    public function itValidatesEveryNewlyReachedWeightMilestone(): void
    {
        $profile = new Profile();
        $firstReachedMilestone = $this->createWeightMilestone('Sous 100 kg', 99.9);
        $secondReachedMilestone = $this->createWeightMilestone('Sous 95 kg', 94.9);
        $futureMilestone = $this->createWeightMilestone('Sous 90 kg', 89.9);
        $measurementDate = new \DateTimeImmutable('2026-08-30 08:00:00');
        $profile
            ->addMilestone($firstReachedMilestone)
            ->addMilestone($secondReachedMilestone)
            ->addMilestone($futureMilestone);

        $validatedMilestoneCount = $this->milestoneService->validateReachedWeightMilestones(
            $profile,
            94.9,
            $measurementDate,
        );

        self::assertSame(2, $validatedMilestoneCount);
        self::assertSame($measurementDate, $firstReachedMilestone->getAchievedAt());
        self::assertSame($measurementDate, $secondReachedMilestone->getAchievedAt());
        self::assertNull($futureMilestone->getAchievedAt());
    }

    #[Test]
    public function itKeepsAnExistingValidationDateAndIgnoresOtherMilestoneTypes(): void
    {
        $profile = new Profile();
        $existingValidationDate = new \DateTimeImmutable('2026-08-01');
        $alreadyReachedMilestone = $this->createWeightMilestone('Déjà atteint', 100.0)
            ->setAchievedAt($existingValidationDate);
        $nonWeightMilestone = (new Milestone())
            ->setTitle('Autre type')
            ->setType('ACTIVITY')
            ->setTargetValue(100.0);
        $profile
            ->addMilestone($alreadyReachedMilestone)
            ->addMilestone($nonWeightMilestone);

        $validatedMilestoneCount = $this->milestoneService->validateReachedWeightMilestones(
            $profile,
            90.0,
            new \DateTimeImmutable('2026-08-30'),
        );

        self::assertSame(0, $validatedMilestoneCount);
        self::assertSame($existingValidationDate, $alreadyReachedMilestone->getAchievedAt());
        self::assertNull($nonWeightMilestone->getAchievedAt());
    }

    #[Test]
    public function itRecognizesTheFrenchWeightMilestoneTypeRegardlessOfCase(): void
    {
        $profile = new Profile();
        $frenchWeightMilestone = (new Milestone())
            ->setTitle('Jalon en français')
            ->setType('poids')
            ->setTargetValue(95.0);
        $profile->addMilestone($frenchWeightMilestone);

        $validatedMilestoneCount = $this->milestoneService->validateReachedWeightMilestones(
            $profile,
            95.0,
            new \DateTimeImmutable('2026-08-30'),
        );

        self::assertSame(1, $validatedMilestoneCount);
        self::assertNotNull($frenchWeightMilestone->getAchievedAt());
    }

    private function createWeightMilestone(string $title, float $targetWeight): Milestone
    {
        return (new Milestone())
            ->setTitle($title)
            ->setType('weight')
            ->setTargetValue($targetWeight);
    }
}
