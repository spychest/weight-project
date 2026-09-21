<?php

namespace App\Entity;

use App\Repository\RecipeViewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipeViewRepository::class)]
#[ORM\Table(name: 'recipe_view')]
#[ORM\UniqueConstraint(name: 'unique_recipe_profile_view', columns: ['recipe_id', 'profile_id'])]
class RecipeView
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'views')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Recipe $recipe = null;

    #[ORM\ManyToOne(inversedBy: 'recipeViews')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Profile $profile = null;

    #[ORM\Column]
    private \DateTimeImmutable $viewedAt;

    public function __construct()
    {
        $this->viewedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getRecipe(): ?Recipe { return $this->recipe; }
    public function setRecipe(Recipe $recipe): static { $this->recipe = $recipe; return $this; }
    public function getProfile(): ?Profile { return $this->profile; }
    public function setProfile(Profile $profile): static { $this->profile = $profile; return $this; }
    public function getViewedAt(): \DateTimeImmutable { return $this->viewedAt; }
}
