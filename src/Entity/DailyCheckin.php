<?php

namespace App\Entity;

use App\Repository\DailyCheckinRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DailyCheckinRepository::class)]
#[ORM\Index(name: 'idx_daily_checkin_profile_date', columns: ['profile_id', 'date'])]
class DailyCheckin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 10)]
    private ?int $moodLevel = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 10)]
    private ?int $energyLevel = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 10)]
    private ?int $frustrationLevel = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 10)]
    private ?int $painLevel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'dailyCheckins')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Profile $profile = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getMoodLevel(): ?int
    {
        return $this->moodLevel;
    }

    public function setMoodLevel(int $mood): static
    {
        $this->moodLevel = $mood;

        return $this;
    }

    public function getEnergyLevel(): ?int
    {
        return $this->energyLevel;
    }

    public function setEnergyLevel(int $energy): static
    {
        $this->energyLevel = $energy;

        return $this;
    }

    public function getFrustrationLevel(): ?int
    {
        return $this->frustrationLevel;
    }

    public function setFrustrationLevel(int $frustration): static
    {
        $this->frustrationLevel = $frustration;

        return $this;
    }

    public function getPainLevel(): ?int
    {
        return $this->painLevel;
    }

    public function setPainLevel(?int $painLevel): static
    {
        $this->painLevel = $painLevel;

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
