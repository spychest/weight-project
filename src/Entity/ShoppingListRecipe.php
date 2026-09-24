<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shopping_list_recipe')]
class ShoppingListRecipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'recipeSelections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ShoppingList $shoppingList = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Recipe $recipe = null;

    #[ORM\Column(length: 255)]
    private string $recipeTitle = '';

    /** @var list<array{name: string, unit: string, quantity: float|int|string}> */
    #[ORM\Column(type: Types::JSON)]
    private array $ingredientSnapshot = [];

    #[ORM\Column]
    private int $preparationCount = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShoppingList(): ?ShoppingList
    {
        return $this->shoppingList;
    }

    public function setShoppingList(ShoppingList $shoppingList): static
    {
        $this->shoppingList = $shoppingList;

        return $this;
    }

    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }

    public function setRecipe(?Recipe $recipe): static
    {
        $this->recipe = $recipe;

        return $this;
    }

    public function getRecipeTitle(): string
    {
        return $this->recipeTitle;
    }

    public function setRecipeTitle(string $recipeTitle): static
    {
        $this->recipeTitle = trim($recipeTitle);

        return $this;
    }

    /** @return list<array{name: string, unit: string, quantity: float|int|string}> */
    public function getIngredientSnapshot(): array
    {
        return $this->ingredientSnapshot;
    }

    /** @param list<array{name: string, unit: string, quantity: float|int|string}> $ingredients */
    public function setIngredientSnapshot(array $ingredients): static
    {
        $this->ingredientSnapshot = $ingredients;

        return $this;
    }

    public function getPreparationCount(): int
    {
        return $this->preparationCount;
    }

    public function setPreparationCount(int $preparationCount): static
    {
        $this->preparationCount = max(1, $preparationCount);

        return $this;
    }
}
