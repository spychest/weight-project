<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
class Profile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $height = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(nullable: true)]
    private ?float $startingWeight = null;

    #[ORM\Column(nullable: true)]
    private ?float $targetWeight = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(float $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeImmutable $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getStartingWeight(): ?float
    {
        return $this->startingWeight;
    }

    public function setStartingWeight(?float $startingWeight): static
    {
        $this->startingWeight = $startingWeight;

        return $this;
    }

    public function getTargetWeight(): ?float
    {
        return $this->targetWeight;
    }

    public function setTargetWeight(?float $targetWeight): static
    {
        $this->targetWeight = $targetWeight;

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
}
