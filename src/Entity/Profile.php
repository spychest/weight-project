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
    private float $height;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $birthDate;

    #[ORM\Column(nullable: true)]
    private ?float $startingWeight = null;

    #[ORM\Column(nullable: true)]
    private ?float $targetWeight = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, WeightEntry>
     */
    #[ORM\OneToMany(targetEntity: WeightEntry::class, mappedBy: 'profile')]
    private Collection $weightEntries;

    #[ORM\Column(length: 255)]
    private string $biologicalGender;

    /**
     * @var Collection<int, DailyCheckin>
     */
    #[ORM\OneToMany(targetEntity: DailyCheckin::class, mappedBy: 'profile')]
    private Collection $dailyCheckins;

    /**
     * @var Collection<int, FoodEvent>
     */
    #[ORM\OneToMany(targetEntity: FoodEvent::class, mappedBy: 'profile')]
    private Collection $foodEvents;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'profile')]
    private Collection $activities;

    /**
     * @var Collection<int, Victory>
     */
    #[ORM\OneToMany(targetEntity: Victory::class, mappedBy: 'profile')]
    private Collection $victories;

    /**
     * @var Collection<int, Milestone>
     */
    #[ORM\OneToMany(targetEntity: Milestone::class, mappedBy: 'profile')]
    private Collection $milestones;

    /**
     * @var Collection<int, DrinkEntry>
     */
    #[ORM\OneToMany(targetEntity: DrinkEntry::class, mappedBy: 'profile')]
    private Collection $drinkEntries;

    /**
     * @var Collection<int, SleepEntry>
     */
    #[ORM\OneToMany(targetEntity: SleepEntry::class, mappedBy: 'profile')]
    private Collection $sleepEntries;

    public function __construct()
    {
        $this->weightEntries = new ArrayCollection();
        $this->dailyCheckins = new ArrayCollection();
        $this->foodEvents = new ArrayCollection();
        $this->activities = new ArrayCollection();
        $this->victories = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->milestones = new ArrayCollection();
        $this->drinkEntries = new ArrayCollection();
        $this->sleepEntries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function setHeight(float $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getBirthDate(): \DateTimeImmutable
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

    public function getCreatedAt(): \DateTimeImmutable
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
        $this->weightEntries->removeElement($weightEntry);

        return $this;
    }

    public function getBiologicalGender(): string
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
        $this->dailyCheckins->removeElement($dailyCheckin);

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
        $this->foodEvents->removeElement($foodEvent);

        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): static
    {
        if (!$this->activities->contains($activity)) {
            $this->activities->add($activity);
            $activity->setProfile($this);
        }

        return $this;
    }

    public function removeActivity(Activity $activity): static
    {
        $this->activities->removeElement($activity);

        return $this;
    }

    /**
     * @return Collection<int, Victory>
     */
    public function getVictories(): Collection
    {
        return $this->victories;
    }

    public function addVictory(Victory $victory): static
    {
        if (!$this->victories->contains($victory)) {
            $this->victories->add($victory);
            $victory->setProfile($this);
        }

        return $this;
    }

    public function removeVictory(Victory $victory): static
    {
        $this->victories->removeElement($victory);

        return $this;
    }

    /**
     * @return Collection<int, Milestone>
     */
    public function getMilestones(): Collection
    {
        return $this->milestones;
    }

    public function addMilestone(Milestone $milestone): static
    {
        if (!$this->milestones->contains($milestone)) {
            $this->milestones->add($milestone);
            $milestone->setProfile($this);
        }

        return $this;
    }

    public function removeMilestone(Milestone $milestone): static
    {
        if ($this->milestones->removeElement($milestone)) {
            // set the owning side to null (unless already changed)
            if ($milestone->getProfile() === $this) {
                $milestone->setProfile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DrinkEntry>
     */
    public function getDrinkEntries(): Collection
    {
        return $this->drinkEntries;
    }

    public function addDrinkEntry(DrinkEntry $drinkEntry): static
    {
        if (!$this->drinkEntries->contains($drinkEntry)) {
            $this->drinkEntries->add($drinkEntry);
            $drinkEntry->setProfile($this);
        }

        return $this;
    }

    public function removeDrinkEntry(DrinkEntry $drinkEntry): static
    {
        if ($this->drinkEntries->removeElement($drinkEntry)) {
            // set the owning side to null (unless already changed)
            if ($drinkEntry->getProfile() === $this) {
                $drinkEntry->setProfile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SleepEntry>
     */
    public function getSleepEntries(): Collection
    {
        return $this->sleepEntries;
    }

    public function addSleepEntry(SleepEntry $sleepEntry): static
    {
        if (!$this->sleepEntries->contains($sleepEntry)) {
            $this->sleepEntries->add($sleepEntry);
            $sleepEntry->setProfile($this);
        }

        return $this;
    }

    public function removeSleepEntry(SleepEntry $sleepEntry): static
    {
        if ($this->sleepEntries->removeElement($sleepEntry)) {
            // set the owning side to null (unless already changed)
            if ($sleepEntry->getProfile() === $this) {
                $sleepEntry->setProfile(null);
            }
        }

        return $this;
    }
}
