<?php

namespace App\Entity;

use App\Repository\MotivationPointRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MotivationPointRepository::class)]
#[ORM\Table(name: 'motivation_point')]
#[ORM\Index(name: 'idx_motivation_point_profile_position', columns: ['profile_id', 'position'])]
class MotivationPoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: 'motivationPoints')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Profile $profile = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank(message: 'Écris une motivation avant de l’enregistrer.')]
    #[Assert\Length(max: 500, maxMessage: 'La motivation ne peut pas dépasser {{ limit }} caractères.')]
    private string $content = '';

    #[ORM\Column]
    private int $position = 1;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = trim($content);

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = max(1, $position);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
