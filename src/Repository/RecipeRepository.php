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

    public function paginatePublished(int $page, int $itemsPerPage): PaginatedResult
    {
        return $this->paginate($this->createQueryBuilder('recipe')->orderBy('recipe.createdAt', 'DESC'), $page, $itemsPerPage);
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
