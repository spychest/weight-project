<?php

namespace App\Entity;

use App\Repository\ShoppingListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShoppingListRepository::class)]
#[ORM\Table(name: 'shopping_list')]
class ShoppingList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shoppingLists')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Profile $profile = null;

    #[ORM\Column]
    private bool $active = true;

    /**
     * @var list<array{
     *     key: string,
     *     name: string,
     *     quantity: float,
     *     calculatedQuantity: float,
     *     unit: string,
     *     category: string,
     *     categoryOverridden?: bool,
     *     checked: bool,
     *     manual: bool
     * }>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $items = [];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $excludedGeneratedItemKeys = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column]
    private \DateTimeImmutable $lastRecipeAddedAt;

    /** @var Collection<int, ShoppingListRecipe> */
    #[ORM\OneToMany(
        targetEntity: ShoppingListRecipe::class,
        mappedBy: 'shoppingList',
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    private Collection $recipeSelections;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->lastRecipeAddedAt = new \DateTimeImmutable();
        $this->recipeSelections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return list<array{
     *     key: string,
     *     name: string,
     *     quantity: float,
     *     calculatedQuantity: float,
     *     unit: string,
     *     category: string,
     *     categoryOverridden?: bool,
     *     checked: bool,
     *     manual: bool
     * }>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param list<array{
     *     key: string,
     *     name: string,
     *     quantity: float,
     *     calculatedQuantity: float,
     *     unit: string,
     *     category: string,
     *     categoryOverridden?: bool,
     *     checked: bool,
     *     manual: bool
     * }> $items
     */
    public function setItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    /** @return list<string> */
    public function getExcludedGeneratedItemKeys(): array
    {
        return $this->excludedGeneratedItemKeys;
    }

    public function excludeGeneratedItem(string $key): static
    {
        if (!in_array($key, $this->excludedGeneratedItemKeys, true)) {
            $this->excludedGeneratedItemKeys[] = $key;
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): static
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getLastRecipeAddedAt(): \DateTimeImmutable
    {
        return $this->lastRecipeAddedAt;
    }

    public function markRecipeAdded(): static
    {
        $this->lastRecipeAddedAt = new \DateTimeImmutable();

        return $this->touch();
    }

    public function getDisplayName(): string
    {
        return 'Liste de courses — '.$this->lastRecipeAddedAt->format('d/m/Y');
    }

    /** @return Collection<int, ShoppingListRecipe> */
    public function getRecipeSelections(): Collection
    {
        return $this->recipeSelections;
    }

    public function addRecipeSelection(ShoppingListRecipe $selection): static
    {
        if (!$this->recipeSelections->contains($selection)) {
            $this->recipeSelections->add($selection);
            $selection->setShoppingList($this);
        }

        return $this;
    }

    public function removeRecipeSelection(ShoppingListRecipe $selection): static
    {
        $this->recipeSelections->removeElement($selection);

        return $this;
    }

    public function getRemainingItemCount(): int
    {
        return count(array_filter(
            $this->items,
            static fn (array $item): bool => !$item['checked'],
        ));
    }
}
