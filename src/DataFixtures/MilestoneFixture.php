<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Profile;
use App\Entity\Milestone;

class MilestoneFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $profile = $this->getReference('main_profile', Profile::class);

        $milestones = [
            [
                'title' => 'Sortie de l\'obésité morbide',
                'description' => 'Passer sous les 113 kg',
                'type' => 'WEIGHT',
                'targetValue' => 112.9,
            ],
            [
                'title' => 'Retour sous les 100 kg',
                'description' => 'Revenir sous la barre des trois chiffres',
                'type' => 'WEIGHT',
                'targetValue' => 99.9,
            ],
            [
                'title' => 'Retour au poids de 2006',
                'description' => 'Retrouver mon poids le plus bas depuis mon opération de la hanche',
                'type' => 'WEIGHT',
                'targetValue' => 86,
            ],
        ];

        foreach ($milestones as $milestoneData) {
            $milestone = new Milestone();

            $milestone
                ->setTitle($milestoneData['title'])
                ->setDescription($milestoneData['description'])
                ->setType($milestoneData['type'])
                ->setTargetValue($milestoneData['targetValue'])
                ->setProfile($profile);

            $manager->persist($milestone);
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
