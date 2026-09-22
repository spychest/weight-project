<?php

namespace App\Repository;

use App\Entity\FavoriteMeal;
use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FavoriteMeal> */
final class FavoriteMealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FavoriteMeal::class);
    }

    /** @return FavoriteMeal[] */
    public function findForProfile(Profile $profile): array
    {
        return $this->createQueryBuilder('favoriteMeal')
            ->andWhere('favoriteMeal.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('favoriteMeal.name', 'ASC')
            ->addOrderBy('favoriteMeal.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
