<?php

namespace App\DataFixtures;

use App\Entity\DrinkEntry;
use App\Entity\Profile;
use App\Enum\DrinkType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DrinkEntryFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $profile = $this->getReference('main_profile', Profile::class);

        $drink = new DrinkEntry();

        $drink
            ->setProfile($profile)
            ->setDate(new \DateTimeImmutable('today'))
            ->setDrinkType(DrinkType::WATER)
            ->setQuantity(1500);

        $manager->persist($drink);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProfileFixture::class,
        ];
    }
}