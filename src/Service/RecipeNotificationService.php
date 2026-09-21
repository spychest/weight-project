<?php

namespace App\Service;

use App\Repository\RecipeRepository;

final class RecipeNotificationService
{
    private ?int $cachedUnseenRecipeCount = null;

    public function __construct(
        private readonly CurrentUserProfileProvider $currentUserProfileProvider,
        private readonly RecipeRepository $recipeRepository,
    ) {}

    public function getUnseenRecipeCount(): int
    {
        if ($this->cachedUnseenRecipeCount !== null) {
            return $this->cachedUnseenRecipeCount;
        }

        $profile = $this->currentUserProfileProvider->getProfile();

        return $this->cachedUnseenRecipeCount = $profile === null ? 0 : $this->recipeRepository->countUnseenForProfile($profile);
    }
}
