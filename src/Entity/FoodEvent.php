<?php

namespace App\Entity;

use App\Enum\MealType;
use App\Repository\FoodEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FoodEventRepository::class)]
class FoodEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $eatenAt = null;

    #[ORM\Column(enumType: MealType::class)]
    private ?MealType $mealType = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 10
    )]
    private ?int $hungerLevel = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 10
    )]
    private ?int $pleasureLevel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cause = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'foodEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Profile $profile = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEatenAt(): ?\DateTimeImmutable
    {
        return $this->eatenAt;
    }

    public function setEatenAt(\DateTimeImmutable $eatenAt): static
    {
        $this->eatenAt = $eatenAt;

        return $this;
    }

    public function getMealType(): ?MealType
    {
        return $this->mealType;
    }

    public function setMealType(?MealType $mealType): static
    {
        $this->mealType = $mealType;

        return $this;
    }


    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getHungerLevel(): ?int
    {
        return $this->hungerLevel;
    }

    public function setHungerLevel(?int $hungerLevel): static
    {
        $this->hungerLevel = $hungerLevel;

        return $this;
    }

    public function getPleasureLevel(): ?int
    {
        return $this->pleasureLevel;
    }

    public function setPleasureLevel(?int $pleasureLevel): static
    {
        $this->pleasureLevel = $pleasureLevel;

        return $this;
    }

    public function getCause(): ?string
    {
        return $this->cause;
    }

    public function setCause(?string $cause): static
    {
        $this->cause = $cause;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }
}
