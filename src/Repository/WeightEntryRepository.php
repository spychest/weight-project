<?php

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\WeightEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeightEntry>
 */
class WeightEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeightEntry::class);
    }

    public function findLatestForProfile(Profile $profile): ?WeightEntry
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('w.measuredAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return WeightEntry[]
     */
    public function findForPeriod(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): array {
        $endDateExclusive = $endDate->modify('+1 day');

        return $this->createQueryBuilder('w')
            ->andWhere('w.profile = :profile')
            ->andWhere('w.measuredAt >= :startDate')
            ->andWhere('w.measuredAt < :endDateExclusive')
            ->setParameter('profile', $profile)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDateExclusive', $endDateExclusive)
            ->orderBy('w.measuredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return WeightEntry[] Returns an array of WeightEntry objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('w.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?WeightEntry
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
