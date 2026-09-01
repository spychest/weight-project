<?php

namespace App\Repository;

use App\Entity\DrinkEntry;
use App\Entity\Profile;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractPaginatedRepository<DrinkEntry>
 */
class DrinkEntryRepository extends AbstractPaginatedRepository
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

    /** @return DrinkEntry[] */
    public function findAllForProfile(Profile $profile): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('d.date', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function paginateAllForProfileFromNewest(Profile $profile, int $page, int $itemsPerPage): \App\Pagination\PaginatedResult
    {
        $queryBuilder = $this->createQueryBuilder('drinkEntry')
            ->andWhere('drinkEntry.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('drinkEntry.date', 'DESC')
            ->addOrderBy('drinkEntry.id', 'DESC');

        return $this->paginate($queryBuilder, $page, $itemsPerPage);
    }

    /** @return DrinkEntry[] */
    public function findRecentForProfile(Profile $profile, int $maximumResults): array
    {
        return $this->createQueryBuilder('drinkEntry')
            ->andWhere('drinkEntry.profile = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('drinkEntry.date', 'DESC')
            ->addOrderBy('drinkEntry.id', 'DESC')
            ->setMaxResults($maximumResults)
            ->getQuery()
            ->getResult();
    }
}
