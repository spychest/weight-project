<?php

namespace App\Repository;

use App\Entity\DrinkEntry;
use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DrinkEntry>
 */
class DrinkEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DrinkEntry::class);
    }

    /**
     * @return DrinkEntry[]
     */
    public function findForPeriod(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): array {
        $endDateExclusive = $endDate->modify('+1 day');

        return $this->createQueryBuilder('d')
            ->andWhere('d.profile = :profile')
            ->andWhere('d.date >= :startDate')
            ->andWhere('d.date < :endDateExclusive')
            ->setParameter('profile', $profile)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDateExclusive', $endDateExclusive)
            ->orderBy('d.date', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return DrinkEntry[] Returns an array of DrinkEntry objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DrinkEntry
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
