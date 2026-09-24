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
    #[ORM\OneToOne(inversedBy: 'profile')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;
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

    #[ORM\Column(length: 100)]
    private string $displayName = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarFilename = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $googleAvatarUrl = null;

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

    /** @var Collection<int, FavoriteMeal> */
    #[ORM\OneToMany(targetEntity: FavoriteMeal::class, mappedBy: 'profile', orphanRemoval: true)]
    private Collection $favoriteMeals;

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

    /** @var Collection<int, Recipe> */
    #[ORM\OneToMany(targetEntity: Recipe::class, mappedBy: 'profile', orphanRemoval: true)]
    private Collection $recipes;

    /** @var Collection<int, RecipeView> */
    #[ORM\OneToMany(targetEntity: RecipeView::class, mappedBy: 'profile', orphanRemoval: true)]
    private Collection $recipeViews;

    /** @var Collection<int, ShoppingList> */
    #[ORM\OneToMany(targetEntity: ShoppingList::class, mappedBy: 'profile', orphanRemoval: true)]
    private Collection $shoppingLists;

    public function __construct()
    {
        $this->weightEntries = new ArrayCollection();
        $this->dailyCheckins = new ArrayCollection();
        $this->foodEvents = new ArrayCollection();
        $this->favoriteMeals = new ArrayCollection();
        $this->activities = new ArrayCollection();
        $this->victories = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->milestones = new ArrayCollection();
        $this->drinkEntries = new ArrayCollection();
        $this->sleepEntries = new ArrayCollection();
        $this->recipes = new ArrayCollection();
        $this->recipeViews = new ArrayCollection();
        $this->shoppingLists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; if ($user?->getProfile() !== $this) { $user?->setProfile($this); } return $this; }

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

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = trim($displayName);

        return $this;
    }

    public function getAvatarFilename(): ?string
    {
        return $this->avatarFilename;
    }

    public function setAvatarFilename(?string $avatarFilename): static
    {
        $this->avatarFilename = $avatarFilename;

        return $this;
    }

    public function getGoogleAvatarUrl(): ?string
    {
        return $this->googleAvatarUrl;
    }

    public function setGoogleAvatarUrl(?string $googleAvatarUrl): static
    {
        $this->googleAvatarUrl = $googleAvatarUrl;

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

    /** @return Collection<int, FavoriteMeal> */
    public function getFavoriteMeals(): Collection
    {
        return $this->favoriteMeals;
    }

    public function addFavoriteMeal(FavoriteMeal $favoriteMeal): static
    {
        if (!$this->favoriteMeals->contains($favoriteMeal)) {
            $this->favoriteMeals->add($favoriteMeal);
            $favoriteMeal->setProfile($this);
        }

        return $this;
    }

    public function removeFavoriteMeal(FavoriteMeal $favoriteMeal): static
    {
        if ($this->favoriteMeals->removeElement($favoriteMeal) && $favoriteMeal->getProfile() === $this) {
            $favoriteMeal->setProfile(null);
        }

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

    /** @return Collection<int, Recipe> */
    public function getRecipes(): Collection { return $this->recipes; }

    public function addRecipe(Recipe $recipe): static
    {
        if (!$this->recipes->contains($recipe)) {
            $this->recipes->add($recipe);
            $recipe->setProfile($this);
        }

        return $this;
    }

    public function removeRecipe(Recipe $recipe): static
    {
        if ($this->recipes->removeElement($recipe) && $recipe->getProfile() === $this) {
            $recipe->setProfile(null);
        }

        return $this;
    }

    /** @return Collection<int, RecipeView> */
    public function getRecipeViews(): Collection { return $this->recipeViews; }

    /** @return Collection<int, ShoppingList> */
    public function getShoppingLists(): Collection
    {
        return $this->shoppingLists;
    }
}
