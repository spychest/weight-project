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
}
