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

    private function createWeightMilestone(string $title, float $targetWeight): Milestone
    {
        return (new Milestone())
            ->setTitle($title)
            ->setType('weight')
            ->setTargetValue($targetWeight);
    }
}
