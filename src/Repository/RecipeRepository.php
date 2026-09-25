<?php

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\Recipe;
use App\Pagination\PaginatedResult;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractPaginatedRepository<Recipe> */
final class RecipeRepository extends AbstractPaginatedRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    public function paginatePublished(
        int $page,
        int $itemsPerPage,
        string $authorDisplayName = '',
        ?\DateTimeImmutable $publicationDate = null,
        bool $vegetarianOnly = false,
        bool $veganOnly = false,
        bool $glutenFreeOnly = false,
    ): PaginatedResult {
        $queryBuilder = $this->createQueryBuilder('recipe')
            ->innerJoin('recipe.profile', 'profile')
            ->addSelect('profile')
            ->orderBy('recipe.createdAt', 'DESC');

        if ($authorDisplayName !== '') {
            $queryBuilder
                ->andWhere('LOWER(profile.displayName) LIKE :authorDisplayName')
                ->setParameter('authorDisplayName', '%'.mb_strtolower($authorDisplayName).'%');
        }

        if ($publicationDate !== null) {
            $queryBuilder
                ->andWhere('recipe.createdAt >= :publicationDateStart')
                ->andWhere('recipe.createdAt < :publicationDateEnd')
                ->setParameter('publicationDateStart', $publicationDate)
                ->setParameter('publicationDateEnd', $publicationDate->modify('+1 day'));
        }

        if ($vegetarianOnly) {
            $queryBuilder->andWhere('recipe.vegetarian = true');
        }
        if ($veganOnly) {
            $queryBuilder->andWhere('recipe.vegan = true');
        }
        if ($glutenFreeOnly) {
            $queryBuilder->andWhere('recipe.glutenFree = true');
        }

        return $this->paginate($queryBuilder, $page, $itemsPerPage);
    }

    public function paginateForProfile(Profile $profile, int $page, int $itemsPerPage): PaginatedResult
    {
        return $this->paginate(
            $this->createQueryBuilder('recipe')
                ->andWhere('recipe.profile = :profile')
                ->setParameter('profile', $profile)
                ->orderBy('recipe.updatedAt', 'DESC'),
            $page,
            $itemsPerPage,
        );
    }

    public function countUnseenForProfile(Profile $profile): int
    {
        return (int) $this->createQueryBuilder('recipe')
            ->select('COUNT(recipe.id)')
            ->andWhere('recipe.profile != :profile')
            ->andWhere('NOT EXISTS (SELECT recipeView.id FROM App\\Entity\\RecipeView recipeView WHERE recipeView.recipe = recipe AND recipeView.profile = :profile)')
            ->setParameter('profile', $profile)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
