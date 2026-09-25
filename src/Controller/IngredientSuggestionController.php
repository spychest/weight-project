<?php

namespace App\Controller;

use App\Service\Shopping\IngredientSuggestionProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class IngredientSuggestionController extends AbstractController
{
    #[Route('/ingredients/suggestions', name: 'app_ingredient_suggestions', methods: ['GET'])]
    public function __invoke(
        Request $request,
        IngredientSuggestionProvider $ingredientSuggestionProvider,
    ): JsonResponse {
        return $this->json([
            'suggestions' => $ingredientSuggestionProvider->findSuggestions(
                (string) $request->query->get('q', ''),
            ),
        ]);
    }
}
