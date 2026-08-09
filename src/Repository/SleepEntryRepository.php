<?php

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\SleepEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SleepEntry>
 */
class SleepEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SleepEntry::class);
    }

    /**
     * @return SleepEntry[]
     */
    public function findForPeriod(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): array {
        $endDateExclusive = $endDate->modify('+1 day');

        return $this->createQueryBuilder('s')
            ->andWhere('s.profile = :profile')
            ->andWhere('s.date >= :startDate')
            ->andWhere('s.date < :endDateExclusive')
            ->setParameter('profile', $profile)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDateExclusive', $endDateExclusive)
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return SleepEntry[] Returns an array of SleepEntry objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SleepEntry
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
