<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /**
     * @return Activity[]
     */
    public function findForPeriod(
        Profile $profile,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): array {
        $endDateExclusive = $endDate->modify('+1 day');

        return $this->createQueryBuilder('a')
            ->andWhere('a.profile = :profile')
            ->andWhere('a.date >= :startDate')
            ->andWhere('a.date < :endDateExclusive')
            ->setParameter('profile', $profile)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDateExclusive', $endDateExclusive)
            ->orderBy('a.date', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Activity[] */
    public function findRecentForProfile(Profile $profile, int $maximumResults): array
    {
        return $this->createQueryBuilder('activity')
            ->andWhere('activity.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('activity.date', 'DESC')
            ->addOrderBy('activity.id', 'DESC')
            ->setMaxResults($maximumResults)
            ->getQuery()
            ->getResult();
    }
}
