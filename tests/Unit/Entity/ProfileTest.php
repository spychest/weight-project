<?php

namespace App\Tests\Unit\Entity;

use App\Entity\DrinkEntry;
use App\Entity\Milestone;
use App\Entity\Profile;
use App\Entity\WeightEntry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProfileTest extends TestCase
{
    #[Test]
    public function itStoresThePublicIdentityWithoutExposingTheAccountEmail(): void
    {
        $profile = (new Profile())
            ->setDisplayName('  Camille  ')
            ->setAvatarFilename('camille.webp')
            ->setGoogleAvatarUrl('https://example.test/google-avatar.jpg');

        self::assertSame('Camille', $profile->getDisplayName());
        self::assertSame('camille.webp', $profile->getAvatarFilename());
        self::assertSame('https://example.test/google-avatar.jpg', $profile->getGoogleAvatarUrl());
    }

    #[Test]
    public function itKeepsTheWeightEntryRelationshipSynchronized(): void
    {
        $profile = new Profile();
        $weightEntry = new WeightEntry();

        $profile->addWeightEntry($weightEntry);

        self::assertTrue($profile->getWeightEntries()->contains($weightEntry));
        self::assertSame($profile, $weightEntry->getProfile());

        $profile->removeWeightEntry($weightEntry);

        self::assertFalse($profile->getWeightEntries()->contains($weightEntry));
    }

    #[Test]
    public function itClearsTheOwningSideWhenAMilestoneIsRemoved(): void
    {
        $profile = new Profile();
        $milestone = new Milestone();
        $profile->addMilestone($milestone);

        $profile->removeMilestone($milestone);

        self::assertFalse($profile->getMilestones()->contains($milestone));
        self::assertNull($milestone->getProfile());
    }

    #[Test]
    public function itDoesNotDuplicateTheSameDrinkEntry(): void
    {
        $profile = new Profile();
        $drinkEntry = new DrinkEntry();

        $profile->addDrinkEntry($drinkEntry);
        $profile->addDrinkEntry($drinkEntry);

        self::assertCount(1, $profile->getDrinkEntries());
        self::assertSame($profile, $drinkEntry->getProfile());
    }
}
