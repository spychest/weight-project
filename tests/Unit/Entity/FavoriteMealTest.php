<?php

namespace App\Tests\Unit\Entity;

use App\Entity\FavoriteMeal;
use App\Entity\Profile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FavoriteMealTest extends TestCase
{
    #[Test]
    public function profileCanOwnAnEditableFavoriteMeal(): void
    {
        $profile = new Profile();
        $favoriteMeal = (new FavoriteMeal())
            ->setName('Petit-déjeuner habituel')
            ->setDescription('Deux œufs, du pain complet et un fruit');

        $profile->addFavoriteMeal($favoriteMeal);

        self::assertSame($profile, $favoriteMeal->getProfile());
        self::assertTrue($profile->getFavoriteMeals()->contains($favoriteMeal));
        self::assertSame('Petit-déjeuner habituel', $favoriteMeal->getName());
        self::assertSame('Deux œufs, du pain complet et un fruit', $favoriteMeal->getDescription());
    }
}
