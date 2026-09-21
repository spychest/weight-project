<?php

namespace App\Twig;

use App\Service\RecipeNotificationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RecipeExtension extends AbstractExtension
{
    public function __construct(private readonly RecipeNotificationService $recipeNotificationService) {}

    public function getFunctions(): array
    {
        return [new TwigFunction('unseen_recipe_count', $this->recipeNotificationService->getUnseenRecipeCount(...))];
    }
}
