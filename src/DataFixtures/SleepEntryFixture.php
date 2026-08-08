<?php

namespace App\DataFixtures;

use App\Entity\Profile;
use App\Entity\SleepEntry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SleepEntryFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $profile = $this->getReference('main_profile', Profile::class);

        $sleepEntry = new SleepEntry();

        $sleepEntry
            ->setProfile($profile)
            ->setDate(new \DateTimeImmutable('today'))
            ->setBedTime(new \DateTimeImmutable('02:30'))
            ->setWakeUpTime(new \DateTimeImmutable('10:15'))
            ->setQuality(7)
            ->setNote('Nuit correcte, coucher tardif.');

        $manager->persist($sleepEntry);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProfileFixture::class,
        ];
    }
}