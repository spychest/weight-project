<?php

namespace App\DataFixtures;

use App\Entity\FoodEvent;
use App\Entity\Profile;
use App\Enum\MealType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class FoodEventFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $profile = $this->getReference('main_profile', Profile::class);

        $foodEvent = new FoodEvent();

        $foodEvent
            ->setProfile($profile)
            ->setMealType(MealType::LUNCH)
            ->setEatenAt(new \DateTimeImmutable('today 12:30'))
            ->setDescription('Pâtes bolognaises')
            ->setHungerLevel(8)
            ->setPleasureLevel(9);

        $manager->persist($foodEvent);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProfileFixture::class,
        ];
    }
}