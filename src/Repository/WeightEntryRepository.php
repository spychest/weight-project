<?php

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\WeightEntry;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractPaginatedRepository<WeightEntry>
 */
class WeightEntryRepository extends AbstractPaginatedRepository
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

    public function findAllForProfile(Profile $profile): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('w.measuredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function paginateAllForProfileFromNewest(Profile $profile, int $page, int $itemsPerPage): \App\Pagination\PaginatedResult
    {
        $queryBuilder = $this->createQueryBuilder('weightEntry')
            ->andWhere('weightEntry.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('weightEntry.measuredAt', 'DESC')
            ->addOrderBy('weightEntry.id', 'DESC');

        return $this->paginate($queryBuilder, $page, $itemsPerPage);
    }
}
