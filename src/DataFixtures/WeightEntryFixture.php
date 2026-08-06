<?php

namespace App\DataFixtures;

use App\Entity\Profile;
use App\Entity\WeightEntry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class WeightEntryFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $profile = $this->getReference('main_profile', Profile::class);

        $entries = [
            [
                'date' => '2026-08-06',
                'weight' => 150.0,
                'note' => 'Début du suivi',
            ],
            [
                'date' => '2026-08-13',
                'weight' => 149.6,
                'note' => 'Première semaine',
            ],
            [
                'date' => '2026-08-20',
                'weight' => 149.9,
                'note' => 'Petite remontée normale',
            ],
            [
                'date' => '2026-08-27',
                'weight' => 148.7,
                'note' => 'Bonne semaine',
            ],
            [
                'date' => '2026-09-03',
                'weight' => 148.2,
                'note' => 'Progression régulière',
            ],
        ];

        foreach ($entries as $entryData) {
            $entry = new WeightEntry();

            $entry
                ->setWeight($entryData['weight'])
                ->setMeasuredAt(new \DateTimeImmutable($entryData['date']))
                ->setNote($entryData['note'])
                ->setProfile($profile);

            $manager->persist($entry);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProfileFixture::class,
        ];
    }
}
