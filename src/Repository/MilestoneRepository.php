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
}
