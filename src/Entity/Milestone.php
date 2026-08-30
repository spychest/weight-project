<?php

namespace App\Entity;

use App\Repository\MilestoneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MilestoneRepository::class)]
class Milestone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?float $targetValue = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $achievedAt = null;

    #[ORM\ManyToOne(inversedBy: 'milestones')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Profile $profile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTargetValue(): ?float
    {
        return $this->targetValue;
    }

    public function setTargetValue(float $targetValue): static
    {
        $this->targetValue = $targetValue;

        return $this;
    }

    public function getAchievedAt(): ?\DateTimeImmutable
    {
        return $this->achievedAt;
    }

    public function setAchievedAt(?\DateTimeImmutable $achievedAt): static
    {
        $this->achievedAt = $achievedAt;

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
