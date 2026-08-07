<?php

namespace App\DataFixtures;

use App\Entity\DailyCheckin;
use App\Entity\Profile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DailyCheckinFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $profile = $this->getReference('main_profile', Profile::class);

        $checkin = new DailyCheckin();

        $checkin
            ->setProfile($profile)
            ->setDate(new \DateTimeImmutable('today'))
            ->setMoodLevel(7)
            ->setEnergyLevel(5)
            ->setFrustrationLevel(3)
            ->setPainLevel(2)
            ->setNote('Journée correcte, fatigue présente mais motivation bonne.');

        $manager->persist($checkin);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProfileFixture::class,
        ];
    }
}