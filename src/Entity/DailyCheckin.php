<?php

namespace App\Entity;

use App\Repository\DailyCheckinRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DailyCheckinRepository::class)]
class DailyCheckin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    private ?int $mood = null;

    #[ORM\Column]
    private ?int $energy = null;

    #[ORM\Column]
    private ?int $frustration = null;

    #[ORM\Column(nullable: true)]
    private ?int $painLevel = null;

    #[ORM\ManyToOne(inversedBy: 'dailyCheckins')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Profile $profile = null;

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

    public function getMood(): ?int
    {
        return $this->mood;
    }

    public function setMood(int $mood): static
    {
        $this->mood = $mood;

        return $this;
    }

    public function getEnergy(): ?int
    {
        return $this->energy;
    }

    public function setEnergy(int $energy): static
    {
        $this->energy = $energy;

        return $this;
    }

    public function getFrustration(): ?int
    {
        return $this->frustration;
    }

    public function setFrustration(int $frustration): static
    {
        $this->frustration = $frustration;

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
