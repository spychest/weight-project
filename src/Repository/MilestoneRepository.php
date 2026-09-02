<?php

namespace App\Repository;

use App\Entity\Milestone;
use App\Entity\Profile;
use App\Pagination\PaginatedResult;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractPaginatedRepository<Milestone>
 */
class MilestoneRepository extends AbstractPaginatedRepository
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

    public function paginateForProfileByCompletionStatus(
        Profile $profile,
        bool $completedMilestones,
        int $page,
        int $itemsPerPage,
    ): PaginatedResult
    {
        $queryBuilder = $this->createQueryBuilder('milestone')
            ->andWhere('milestone.profile = :profile')
            ->setParameter('profile', $profile)
            ->andWhere($completedMilestones ? 'milestone.achievedAt IS NOT NULL' : 'milestone.achievedAt IS NULL')
            ->orderBy('milestone.targetValue', 'DESC')
            ->addOrderBy('milestone.id', 'DESC');

        return $this->paginate($queryBuilder, $page, $itemsPerPage);
    }
}
