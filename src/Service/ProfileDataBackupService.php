<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\DailyCheckin;
use App\Entity\DrinkEntry;
use App\Entity\FoodEvent;
use App\Entity\Milestone;
use App\Entity\Profile;
use App\Entity\SleepEntry;
use App\Entity\Victory;
use App\Entity\WeightEntry;
use App\Enum\DrinkType;
use App\Enum\MealType;
use Doctrine\ORM\EntityManagerInterface;

final class ProfileDataBackupService
{
    public const FORMAT_VERSION = 1;

    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    /** @return array<string, mixed> */
    public function exportProfile(Profile $profile): array
    {
        return [
            'format' => 'weight-project-profile-backup',
            'version' => self::FORMAT_VERSION,
            'exportedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'profile' => [
                'height' => $profile->getHeight(), 'birthDate' => $profile->getBirthDate()->format('Y-m-d'),
                'startingWeight' => $profile->getStartingWeight(), 'targetWeight' => $profile->getTargetWeight(),
                'biologicalGender' => $profile->getBiologicalGender(), 'createdAt' => $profile->getCreatedAt()->format(DATE_ATOM),
            ],
            'weightEntries' => array_map(static fn (WeightEntry $entry): array => ['weight' => $entry->getWeight(), 'measuredAt' => $entry->getMeasuredAt()->format(DATE_ATOM), 'note' => $entry->getNote()], $profile->getWeightEntries()->toArray()),
            'dailyCheckins' => array_map(static fn (DailyCheckin $entry): array => ['date' => $entry->getDate()?->format(DATE_ATOM), 'moodLevel' => $entry->getMoodLevel(), 'energyLevel' => $entry->getEnergyLevel(), 'frustrationLevel' => $entry->getFrustrationLevel(), 'painLevel' => $entry->getPainLevel(), 'note' => $entry->getNote(), 'createdAt' => $entry->getCreatedAt()?->format(DATE_ATOM)], $profile->getDailyCheckins()->toArray()),
            'foodEvents' => array_map(static fn (FoodEvent $entry): array => ['eatenAt' => $entry->getEatenAt()?->format(DATE_ATOM), 'mealType' => $entry->getMealType()?->value, 'description' => $entry->getDescription(), 'hungerLevel' => $entry->getHungerLevel(), 'pleasureLevel' => $entry->getPleasureLevel(), 'cause' => $entry->getCause(), 'note' => $entry->getNote(), 'createdAt' => $entry->getCreatedAt()?->format(DATE_ATOM)], $profile->getFoodEvents()->toArray()),
            'activities' => array_map(static fn (Activity $entry): array => ['date' => $entry->getDate()->format(DATE_ATOM), 'description' => $entry->getDescription(), 'painLevel' => $entry->getPainLevel(), 'note' => $entry->getNote(), 'createdAt' => $entry->getCreatedAt()?->format(DATE_ATOM)], $profile->getActivities()->toArray()),
            'victories' => array_map(static fn (Victory $entry): array => ['date' => $entry->getDate()?->format(DATE_ATOM), 'title' => $entry->getTitle(), 'description' => $entry->getDescription(), 'category' => $entry->getCategory(), 'importance' => $entry->getImportance(), 'createdAt' => $entry->getCreatedAt()?->format(DATE_ATOM)], $profile->getVictories()->toArray()),
            'milestones' => array_map(static fn (Milestone $entry): array => ['title' => $entry->getTitle(), 'description' => $entry->getDescription(), 'type' => $entry->getType(), 'targetValue' => $entry->getTargetValue(), 'achievedAt' => $entry->getAchievedAt()?->format(DATE_ATOM)], $profile->getMilestones()->toArray()),
            'drinkEntries' => array_map(static fn (DrinkEntry $entry): array => ['date' => $entry->getDate()?->format(DATE_ATOM), 'drinkType' => $entry->getDrinkType()?->value, 'quantity' => $entry->getQuantity(), 'description' => $entry->getDescription(), 'note' => $entry->getNote(), 'createdAt' => $entry->getCreatedAt()?->format(DATE_ATOM)], $profile->getDrinkEntries()->toArray()),
            'sleepEntries' => array_map(static fn (SleepEntry $entry): array => ['date' => $entry->getDate()->format(DATE_ATOM), 'bedTime' => $entry->getBedTime()->format(DATE_ATOM), 'wakeUpTime' => $entry->getWakeUpTime()->format(DATE_ATOM), 'quality' => $entry->getQuality(), 'note' => $entry->getNote(), 'createdAt' => $entry->getCreatedAt()?->format(DATE_ATOM)], $profile->getSleepEntries()->toArray()),
        ];
    }

    public function importProfile(Profile $profile, string $json): int
    {
        try { $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR); } catch (\JsonException) { throw new \InvalidArgumentException('Le fichier ne contient pas un document JSON valide.'); }
        if (!is_array($data) || ($data['format'] ?? null) !== 'weight-project-profile-backup' || ($data['version'] ?? null) !== self::FORMAT_VERSION || !is_array($data['profile'] ?? null)) {
            throw new \InvalidArgumentException('Ce fichier n’est pas une sauvegarde compatible de Weight Project.');
        }
        $collections = ['weightEntries', 'dailyCheckins', 'foodEvents', 'activities', 'victories', 'milestones', 'drinkEntries', 'sleepEntries'];
        foreach ($collections as $collection) { if (!isset($data[$collection]) || !is_array($data[$collection])) { throw new \InvalidArgumentException(sprintf('La section « %s » est absente ou invalide.', $collection)); } }

        $profileData = $data['profile'];
        $height = $this->requiredFloat($profileData, 'height');
        $birthDate = $this->date($profileData['birthDate'] ?? null, 'birthDate');
        $biologicalGender = $this->requiredString($profileData, 'biologicalGender');
        $startingWeight = $this->nullableFloat($profileData['startingWeight'] ?? null, 'startingWeight');
        $targetWeight = $this->nullableFloat($profileData['targetWeight'] ?? null, 'targetWeight');

        $entities = [];
        foreach ($data['weightEntries'] as $row) { $row = $this->row($row); $entities[] = (new WeightEntry())->setProfile($profile)->setWeight($this->requiredFloat($row, 'weight'))->setMeasuredAt($this->date($row['measuredAt'] ?? null, 'measuredAt'))->setNote($this->nullableString($row['note'] ?? null, 'note')); }
        foreach ($data['dailyCheckins'] as $row) { $row = $this->row($row); $entity = (new DailyCheckin())->setProfile($profile)->setDate($this->date($row['date'] ?? null, 'date'))->setMoodLevel($this->requiredInt($row, 'moodLevel'))->setEnergyLevel($this->requiredInt($row, 'energyLevel'))->setFrustrationLevel($this->requiredInt($row, 'frustrationLevel'))->setPainLevel($this->nullableInt($row['painLevel'] ?? null, 'painLevel'))->setNote($this->nullableString($row['note'] ?? null, 'note')); if (($createdAt = $this->nullableDate($row['createdAt'] ?? null, 'createdAt')) !== null) { $entity->setCreatedAt($createdAt); } $entities[] = $entity; }
        foreach ($data['foodEvents'] as $row) { $row = $this->row($row); $mealType = ($row['mealType'] ?? null) === null ? null : MealType::tryFrom($this->requiredString($row, 'mealType')); if (($row['mealType'] ?? null) !== null && $mealType === null) { throw new \InvalidArgumentException('Un type de repas est invalide.'); } $entity = (new FoodEvent())->setProfile($profile)->setEatenAt($this->date($row['eatenAt'] ?? null, 'eatenAt'))->setMealType($mealType)->setDescription($this->requiredString($row, 'description'))->setHungerLevel($this->nullableInt($row['hungerLevel'] ?? null, 'hungerLevel'))->setPleasureLevel($this->nullableInt($row['pleasureLevel'] ?? null, 'pleasureLevel'))->setCause($this->nullableString($row['cause'] ?? null, 'cause'))->setNote($this->nullableString($row['note'] ?? null, 'note')); if (($createdAt = $this->nullableDate($row['createdAt'] ?? null, 'createdAt')) !== null) { $entity->setCreatedAt($createdAt); } $entities[] = $entity; }
        foreach ($data['activities'] as $row) { $row = $this->row($row); $entity = (new Activity())->setProfile($profile)->setDate($this->date($row['date'] ?? null, 'date'))->setDescription($this->requiredString($row, 'description'))->setPainLevel($this->nullableInt($row['painLevel'] ?? null, 'painLevel'))->setNote($this->nullableString($row['note'] ?? null, 'note')); if (($createdAt = $this->nullableDate($row['createdAt'] ?? null, 'createdAt')) !== null) { $entity->setCreatedAt($createdAt); } $entities[] = $entity; }
        foreach ($data['victories'] as $row) { $row = $this->row($row); $entity = (new Victory())->setProfile($profile)->setDate($this->date($row['date'] ?? null, 'date'))->setTitle($this->requiredString($row, 'title'))->setDescription($this->nullableString($row['description'] ?? null, 'description'))->setCategory($this->nullableString($row['category'] ?? null, 'category'))->setImportance($this->nullableInt($row['importance'] ?? null, 'importance')); if (($createdAt = $this->nullableDate($row['createdAt'] ?? null, 'createdAt')) !== null) { $entity->setCreatedAt($createdAt); } $entities[] = $entity; }
        foreach ($data['milestones'] as $row) { $row = $this->row($row); $entities[] = (new Milestone())->setProfile($profile)->setTitle($this->requiredString($row, 'title'))->setDescription($this->nullableString($row['description'] ?? null, 'description'))->setType($this->requiredString($row, 'type'))->setTargetValue($this->requiredFloat($row, 'targetValue'))->setAchievedAt($this->nullableDate($row['achievedAt'] ?? null, 'achievedAt')); }
        foreach ($data['drinkEntries'] as $row) { $row = $this->row($row); $drinkType = DrinkType::tryFrom($this->requiredString($row, 'drinkType')); if ($drinkType === null) { throw new \InvalidArgumentException('Un type de boisson est invalide.'); } $entity = (new DrinkEntry())->setProfile($profile)->setDate($this->date($row['date'] ?? null, 'date'))->setDrinkType($drinkType)->setQuantity($this->requiredInt($row, 'quantity'))->setDescription($this->nullableString($row['description'] ?? null, 'description'))->setNote($this->nullableString($row['note'] ?? null, 'note')); if (($createdAt = $this->nullableDate($row['createdAt'] ?? null, 'createdAt')) !== null) { $entity->setCreatedAt($createdAt); } $entities[] = $entity; }
        foreach ($data['sleepEntries'] as $row) { $row = $this->row($row); $entity = (new SleepEntry())->setProfile($profile)->setDate($this->date($row['date'] ?? null, 'date'))->setBedTime($this->date($row['bedTime'] ?? null, 'bedTime'))->setWakeUpTime($this->date($row['wakeUpTime'] ?? null, 'wakeUpTime'))->setQuality($this->requiredInt($row, 'quality'))->setNote($this->nullableString($row['note'] ?? null, 'note')); if (($createdAt = $this->nullableDate($row['createdAt'] ?? null, 'createdAt')) !== null) { $entity->setCreatedAt($createdAt); } $entities[] = $entity; }

        $this->entityManager->wrapInTransaction(function () use ($profile, $height, $birthDate, $biologicalGender, $startingWeight, $targetWeight, $entities, $collections): void {
            foreach ([$profile->getWeightEntries(), $profile->getDailyCheckins(), $profile->getFoodEvents(), $profile->getActivities(), $profile->getVictories(), $profile->getMilestones(), $profile->getDrinkEntries(), $profile->getSleepEntries()] as $existingCollection) { foreach ($existingCollection->toArray() as $existingEntity) { $this->entityManager->remove($existingEntity); } }
            $this->entityManager->flush();
            $profile->setHeight($height)->setBirthDate($birthDate)->setBiologicalGender($biologicalGender)->setStartingWeight($startingWeight)->setTargetWeight($targetWeight);
            foreach ($entities as $entity) { $this->entityManager->persist($entity); }
            $this->entityManager->flush();
        });
        return count($entities);
    }

    /** @return array<string, mixed> */ private function row(mixed $row): array { if (!is_array($row)) { throw new \InvalidArgumentException('Une entrée de la sauvegarde est invalide.'); } return $row; }
    private function requiredString(array $row, string $key): string { if (!isset($row[$key]) || !is_string($row[$key])) { throw new \InvalidArgumentException("Le champ « $key » est invalide."); } return $row[$key]; }
    private function nullableString(mixed $value, string $key): ?string { if ($value !== null && !is_string($value)) { throw new \InvalidArgumentException("Le champ « $key » est invalide."); } return $value; }
    private function requiredInt(array $row, string $key): int { if (!isset($row[$key]) || !is_int($row[$key])) { throw new \InvalidArgumentException("Le champ « $key » est invalide."); } return $row[$key]; }
    private function nullableInt(mixed $value, string $key): ?int { if ($value !== null && !is_int($value)) { throw new \InvalidArgumentException("Le champ « $key » est invalide."); } return $value; }
    private function requiredFloat(array $row, string $key): float { $value = $row[$key] ?? null; if (!is_int($value) && !is_float($value)) { throw new \InvalidArgumentException("Le champ « $key » est invalide."); } return (float) $value; }
    private function nullableFloat(mixed $value, string $key): ?float { if ($value !== null && !is_int($value) && !is_float($value)) { throw new \InvalidArgumentException("Le champ « $key » est invalide."); } return $value === null ? null : (float) $value; }
    private function date(mixed $value, string $key): \DateTimeImmutable { if (!is_string($value)) { throw new \InvalidArgumentException("La date « $key » est invalide."); } try { return new \DateTimeImmutable($value); } catch (\Exception) { throw new \InvalidArgumentException("La date « $key » est invalide."); } }
    private function nullableDate(mixed $value, string $key): ?\DateTimeImmutable { return $value === null ? null : $this->date($value, $key); }
}
