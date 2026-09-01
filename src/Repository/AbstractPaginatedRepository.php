<?php

namespace App\Repository;

use App\Pagination\PaginatedResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @template TEntity of object
 * @extends ServiceEntityRepository<TEntity>
 */
abstract class AbstractPaginatedRepository extends ServiceEntityRepository
{
    protected function paginate(QueryBuilder $queryBuilder, int $requestedPage, int $itemsPerPage): PaginatedResult
    {
        if ($itemsPerPage < 1) {
            throw new \InvalidArgumentException('The number of items per page must be greater than zero.');
        }

        $rootAliases = $queryBuilder->getRootAliases();
        $rootAlias = $rootAliases[0] ?? throw new \LogicException('A paginated query requires a root alias.');
        $countQueryBuilder = clone $queryBuilder;
        $totalItems = (int) $countQueryBuilder
            ->select(sprintf('COUNT(%s.id)', $rootAlias))
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalItems / $itemsPerPage));
        $currentPage = min(max(1, $requestedPage), $totalPages);
        $items = $queryBuilder
            ->setFirstResult(($currentPage - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();

        return new PaginatedResult($items, $currentPage, $itemsPerPage, $totalItems, $totalPages);
    }
}
