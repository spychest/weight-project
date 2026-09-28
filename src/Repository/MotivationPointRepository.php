<?php

namespace App\Repository;

use App\Entity\MotivationPoint;
use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<MotivationPoint> */
final class MotivationPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MotivationPoint::class);
    }

    /** @return list<MotivationPoint> */
    public function findForProfile(Profile $profile, ?int $limit = null): array
    {
        $queryBuilder = $this->createQueryBuilder('motivationPoint')
            ->andWhere('motivationPoint.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('motivationPoint.position', 'ASC')
            ->addOrderBy('motivationPoint.id', 'ASC');

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getNextPosition(Profile $profile): int
    {
        $highestPosition = $this->createQueryBuilder('motivationPoint')
            ->select('MAX(motivationPoint.position)')
            ->andWhere('motivationPoint.profile = :profile')
            ->setParameter('profile', $profile)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $highestPosition) + 1;
    }

    public function findAdjacent(MotivationPoint $motivationPoint, string $direction): ?MotivationPoint
    {
        $comparison = $direction === 'up' ? '<' : '>';
        $sortDirection = $direction === 'up' ? 'DESC' : 'ASC';

        return $this->createQueryBuilder('adjacentPoint')
            ->andWhere('adjacentPoint.profile = :profile')
            ->andWhere(sprintf('adjacentPoint.position %s :position', $comparison))
            ->setParameter('profile', $motivationPoint->getProfile())
            ->setParameter('position', $motivationPoint->getPosition())
            ->orderBy('adjacentPoint.position', $sortDirection)
            ->addOrderBy('adjacentPoint.id', $sortDirection)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
