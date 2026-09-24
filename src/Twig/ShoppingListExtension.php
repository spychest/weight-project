<?php

namespace App\Twig;

use App\Repository\ShoppingListRepository;
use App\Service\CurrentUserProfileProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ShoppingListExtension extends AbstractExtension
{
    public function __construct(
        private readonly ShoppingListRepository $shoppingListRepository,
        private readonly CurrentUserProfileProvider $currentUserProfileProvider,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('active_shopping_list_summary', $this->getActiveShoppingListSummary(...))];
    }

    /** @return array{id: int, remaining: int}|null */
    public function getActiveShoppingListSummary(): ?array
    {
        $profile = $this->currentUserProfileProvider->getProfile();
        if ($profile === null) {
            return null;
        }
        $shoppingList = $this->shoppingListRepository->findActiveForProfile($profile);
        if ($shoppingList?->getId() === null) {
            return null;
        }

        return [
            'id' => $shoppingList->getId(),
            'remaining' => $shoppingList->getRemainingItemCount(),
        ];
    }
}
