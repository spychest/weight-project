<?php

namespace App\Entity;

use App\Repository\RecipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecipeRepository::class)]
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'recipes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Profile $profile = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre de la recette est obligatoire.')]
    #[Assert\Length(max: 255)]
    private string $title = '';

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $setupTimeMinutes = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $preparationTimeMinutes = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'Le nombre de portions doit être supérieur à zéro.')]
    private int $servings = 1;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoFilename = null;

    #[ORM\Column]
    private bool $vegetarian = false;

    #[ORM\Column]
    private bool $vegan = false;

    #[ORM\Column]
    private bool $glutenFree = false;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $utensils = [];

    /** @var list<array{name: string, unit: string, quantity: float|int|string}> */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'Ajoute au moins un ingrédient.')]
    private array $ingredients = [['name' => '', 'unit' => '', 'quantity' => '']];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $setupSteps = [];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'Ajoute au moins une étape de préparation.')]
    private array $preparationSteps = [''];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $tips = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, RecipeView> */
    #[ORM\OneToMany(targetEntity: RecipeView::class, mappedBy: 'recipe', orphanRemoval: true)]
    private Collection $views;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->views = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getProfile(): ?Profile { return $this->profile; }
    public function setProfile(?Profile $profile): static { $this->profile = $profile; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = trim($title); return $this; }
    public function getSetupTimeMinutes(): ?int { return $this->setupTimeMinutes; }
    public function setSetupTimeMinutes(?int $minutes): static { $this->setupTimeMinutes = $minutes; return $this; }
    public function getPreparationTimeMinutes(): ?int { return $this->preparationTimeMinutes; }
    public function setPreparationTimeMinutes(?int $minutes): static { $this->preparationTimeMinutes = $minutes; return $this; }
    public function getServings(): int { return $this->servings; }
    public function setServings(int $servings): static { $this->servings = $servings; return $this; }
    public function getPhotoFilename(): ?string { return $this->photoFilename; }
    public function setPhotoFilename(?string $filename): static { $this->photoFilename = $filename; return $this; }
    public function isVegetarian(): bool
    {
        return $this->vegetarian;
    }

    public function setVegetarian(bool $vegetarian): static
    {
        $this->vegetarian = $vegetarian;

        return $this;
    }

    public function isVegan(): bool
    {
        return $this->vegan;
    }

    public function setVegan(bool $vegan): static
    {
        $this->vegan = $vegan;

        return $this;
    }

    public function isGlutenFree(): bool
    {
        return $this->glutenFree;
    }

    public function setGlutenFree(bool $glutenFree): static
    {
        $this->glutenFree = $glutenFree;

        return $this;
    }
    /** @return list<string> */
    public function getUtensils(): array { return $this->utensils; }
    /** @param list<string> $utensils */
    public function setUtensils(array $utensils): static { $this->utensils = array_values(array_filter(array_map('trim', $utensils), static fn (string $value): bool => $value !== '')); return $this; }
    /** @return list<array{name: string, unit: string, quantity: float|int|string}> */
    public function getIngredients(): array { return $this->ingredients; }
    /** @param list<array{name: string, unit: string, quantity: float|int|string}> $ingredients */
    public function setIngredients(array $ingredients): static { $this->ingredients = $ingredients; return $this; }
    /** @return list<string> */
    public function getSetupSteps(): array { return $this->setupSteps; }
    /** @param list<string> $steps */
    public function setSetupSteps(array $steps): static { $this->setupSteps = array_values(array_filter(array_map('trim', $steps), static fn (string $value): bool => $value !== '')); return $this; }
    /** @return list<string> */
    public function getPreparationSteps(): array { return $this->preparationSteps; }
    /** @param list<string> $steps */
    public function setPreparationSteps(array $steps): static { $this->preparationSteps = array_values(array_filter(array_map('trim', $steps), static fn (string $value): bool => $value !== '')); return $this; }
    /** @return list<string> */
    public function getTips(): array { return $this->tips; }
    /** @param list<string> $tips */
    public function setTips(array $tips): static { $this->tips = array_values(array_filter(array_map('trim', $tips), static fn (string $value): bool => $value !== '')); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touch(): static { $this->updatedAt = new \DateTimeImmutable(); return $this; }
    /** @return Collection<int, RecipeView> */
    public function getViews(): Collection { return $this->views; }
    public function getUniqueViewCount(): int { return $this->views->count(); }
}
