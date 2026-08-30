<?php

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Profile>
 */
class ProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profile::class);
    }

    public function findFirstProfile(): ?Profile
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForUser(User $user): ?Profile
    {
        return $this->findOneBy(['user' => $user]);
    }

    /** @return list<Profile> */
    public function findProfilesWithoutUser(): array
    {
        return $this->findBy(['user' => null]);
    }
}
