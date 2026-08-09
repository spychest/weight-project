<?php

namespace App\Repository;

use App\Entity\Milestone;
use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Milestone>
 */
class MilestoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Milestone::class);
    }

    /**
     * @return Milestone[]
     */
    public function findForProfile(Profile $profile): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('m.achievedAt', 'DESC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Milestone[] Returns an array of Milestone objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Milestone
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
