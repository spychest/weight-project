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

    //    /**
    //     * @return FoodEvent[] Returns an array of FoodEvent objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FoodEvent
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
