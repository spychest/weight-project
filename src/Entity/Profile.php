<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /**
     * @var Collection<int, WeightEntry>
     */
    #[ORM\OneToMany(targetEntity: WeightEntry::class, mappedBy: 'profile', orphanRemoval: true)]
    private Collection $weightEntries;

    #[ORM\Column(length: 255)]
    private ?string $biologicalGender = null;

    /**
     * @var Collection<int, DailyCheckin>
     */
    #[ORM\OneToMany(targetEntity: DailyCheckin::class, mappedBy: 'profile')]
    private Collection $dailyCheckins;

    /**
     * @var Collection<int, FoodEvent>
     */
    #[ORM\OneToMany(targetEntity: FoodEvent::class, mappedBy: 'profile', orphanRemoval: true)]
    private Collection $foodEvents;

    public function __construct()
    {
        $this->weightEntries = new ArrayCollection();
        $this->dailyCheckins = new ArrayCollection();
        $this->foodEvents = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, WeightEntry>
     */
    public function getWeightEntries(): Collection
    {
        return $this->weightEntries;
    }

    public function addWeightEntry(WeightEntry $weightEntry): static
    {
        if (!$this->weightEntries->contains($weightEntry)) {
            $this->weightEntries->add($weightEntry);
            $weightEntry->setProfile($this);
        }

        return $this;
    }

    public function removeWeightEntry(WeightEntry $weightEntry): static
    {
        if ($this->weightEntries->removeElement($weightEntry)) {
            // set the owning side to null (unless already changed)
            if ($weightEntry->getProfile() === $this) {
                $weightEntry->setProfile(null);
            }
        }

        return $this;
    }

    public function getBiologicalGender(): ?string
    {
        return $this->biologicalGender;
    }

    public function setBiologicalGender(string $biologicalGender): static
    {
        $this->biologicalGender = $biologicalGender;

        return $this;
    }

    /**
     * @return Collection<int, DailyCheckin>
     */
    public function getDailyCheckins(): Collection
    {
        return $this->dailyCheckins;
    }

    public function addDailyCheckin(DailyCheckin $dailyCheckin): static
    {
        if (!$this->dailyCheckins->contains($dailyCheckin)) {
            $this->dailyCheckins->add($dailyCheckin);
            $dailyCheckin->setProfile($this);
        }

        return $this;
    }

    public function removeDailyCheckin(DailyCheckin $dailyCheckin): static
    {
        if ($this->dailyCheckins->removeElement($dailyCheckin)) {
            // set the owning side to null (unless already changed)
            if ($dailyCheckin->getProfile() === $this) {
                $dailyCheckin->setProfile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FoodEvent>
     */
    public function getFoodEvents(): Collection
    {
        return $this->foodEvents;
    }

    public function addFoodEvent(FoodEvent $foodEvent): static
    {
        if (!$this->foodEvents->contains($foodEvent)) {
            $this->foodEvents->add($foodEvent);
            $foodEvent->setProfile($this);
        }

        return $this;
    }

    public function removeFoodEvent(FoodEvent $foodEvent): static
    {
        if ($this->foodEvents->removeElement($foodEvent)) {
            // set the owning side to null (unless already changed)
            if ($foodEvent->getProfile() === $this) {
                $foodEvent->setProfile(null);
            }
        }

        return $this;
    }
}
