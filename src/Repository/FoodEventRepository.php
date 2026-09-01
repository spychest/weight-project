<?php

namespace App\Repository;

use App\Entity\FoodEvent;
use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FoodEvent>
 */
class FoodEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoodEvent::class);
    }

    /**
     * @return FoodEvent[]
     */
    public function findForPeriod(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): array {
        $endDateExclusive = $endDate->modify('+1 day');

        return $this->createQueryBuilder('f')
            ->andWhere('f.profile = :profile')
            ->andWhere('f.eatenAt >= :startDate')
            ->andWhere('f.eatenAt < :endDateExclusive')
            ->setParameter('profile', $profile)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDateExclusive', $endDateExclusive)
            ->orderBy('f.eatenAt', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return FoodEvent[]
     */
    public function findAllForProfile(Profile $profile): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('f.eatenAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return FoodEvent[] */
    public function findAllForProfileOrderedFromNewest(Profile $profile): array
    {
        return $this->createQueryBuilder('foodEvent')
            ->andWhere('foodEvent.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('foodEvent.eatenAt', 'DESC')
            ->addOrderBy('foodEvent.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return FoodEvent[] */
    public function findRecentForProfile(Profile $profile, int $maximumResults): array
    {
        return $this->createQueryBuilder('foodEvent')
            ->andWhere('foodEvent.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('foodEvent.eatenAt', 'DESC')
            ->addOrderBy('foodEvent.id', 'DESC')
            ->setMaxResults($maximumResults)
            ->getQuery()
            ->getResult();
    }
}
