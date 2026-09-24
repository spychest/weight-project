<?php

namespace App\Entity;

use App\Repository\IngredientCatalogItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IngredientCatalogItemRepository::class)]
#[ORM\Table(name: 'ingredient_catalog_item')]
class IngredientCatalogItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    private string $canonicalName = '';

    #[ORM\Column(length: 160, unique: true)]
    private string $normalizedName = '';

    #[ORM\Column(length: 100)]
    private string $category = 'Autres';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $aliases = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCanonicalName(): string
    {
        return $this->canonicalName;
    }

    public function setCanonicalName(string $canonicalName): static
    {
        $this->canonicalName = trim($canonicalName);

        return $this;
    }

    public function getNormalizedName(): string
    {
        return $this->normalizedName;
    }

    public function setNormalizedName(string $normalizedName): static
    {
        $this->normalizedName = trim($normalizedName);

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = trim($category);

        return $this;
    }

    /** @return list<string> */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /** @param list<string> $aliases */
    public function setAliases(array $aliases): static
    {
        $cleanAliases = array_filter(array_map('trim', $aliases));
        $this->aliases = array_values(array_unique($cleanAliases));

        return $this;
    }
}
