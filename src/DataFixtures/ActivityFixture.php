<?php

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\Profile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ActivityFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $profile = $this->getReference('main_profile', Profile::class);

        $activity = new Activity();

        $activity
            ->setProfile($profile)
            ->setDate(new \DateTimeImmutable('today'))
            ->setDescription('Marche tranquille pendant environ 20 minutes.')
            ->setPainLevel(2)
            ->setNote('Quelques gênes au niveau du genou, mais activité bien tolérée.');

        $manager->persist($activity);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProfileFixture::class,
        ];
    }
}