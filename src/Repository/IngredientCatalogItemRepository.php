<?php

namespace App\Repository;

use App\Entity\IngredientCatalogItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class IngredientCatalogItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IngredientCatalogItem::class);
    }

    /** @return list<IngredientCatalogItem> */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('catalogItem')
            ->orderBy('catalogItem.canonicalName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{items: list<IngredientCatalogItem>, total: int}
     */
    public function searchPaginated(
        string $searchTerm,
        string $category,
        int $page,
        int $itemsPerPage = 100,
    ): array {
        $queryBuilder = $this->createQueryBuilder('catalogItem');

        if ($searchTerm !== '') {
            $normalizedSearchTerm = mb_strtolower($searchTerm);
            $matchingAliasIds = $this->getEntityManager()
                ->getConnection()
                ->fetchFirstColumn(
                    'SELECT id FROM ingredient_catalog_item WHERE LOWER(aliases) LIKE :searchTerm',
                    ['searchTerm' => '%'.$normalizedSearchTerm.'%'],
                );
            $queryBuilder
                ->andWhere(
                    'LOWER(catalogItem.canonicalName) LIKE :searchTerm '
                    .'OR LOWER(catalogItem.normalizedName) LIKE :searchTerm '
                    .'OR catalogItem.id IN (:matchingAliasIds)',
                )
                ->setParameter('searchTerm', '%'.$normalizedSearchTerm.'%')
                ->setParameter('matchingAliasIds', $matchingAliasIds ?: [0]);
        }

        if ($category !== '') {
            $queryBuilder
                ->andWhere('catalogItem.category = :category')
                ->setParameter('category', $category);
        }

        $countQueryBuilder = clone $queryBuilder;
        $total = (int) $countQueryBuilder
            ->select('COUNT(catalogItem.id)')
            ->getQuery()
            ->getSingleScalarResult();

        /** @var list<IngredientCatalogItem> $items */
        $items = $queryBuilder
            ->orderBy('catalogItem.canonicalName', 'ASC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }

    /** @return list<string> */
    public function findDistinctCategories(): array
    {
        /** @var list<array{category: string}> $results */
        $results = $this->createQueryBuilder('catalogItem')
            ->select('DISTINCT catalogItem.category AS category')
            ->orderBy('catalogItem.category', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_column($results, 'category');
    }
}
