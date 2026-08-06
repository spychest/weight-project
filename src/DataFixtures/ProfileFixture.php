<?php

namespace App\DataFixtures;

use App\Entity\Profile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProfileFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $profile = new Profile();

        $profile
            ->setHeight(1.69)
            ->setBirthDate(new \DateTimeImmutable('1991-04-08'))
            ->setBiologicalGender('male')
            ->setStartingWeight(150)
            ->setTargetWeight(80);

        $manager->persist($profile);

        $manager->flush();

        $this->addReference('main_profile', $profile);
    }
}