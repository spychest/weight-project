<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/** @extends ServiceEntityRepository<User> */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => mb_strtolower(trim($email))]);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException();
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }

    /** @return array{items: list<User>, total: int} */
    public function searchPaginated(string $searchTerm, int $page, int $itemsPerPage = 100): array
    {
        $queryBuilder = $this->createQueryBuilder('user')
            ->leftJoin('user.profile', 'profile')
            ->addSelect('profile');

        if ($searchTerm !== '') {
            $queryBuilder
                ->andWhere('LOWER(user.email) LIKE :searchTerm')
                ->setParameter('searchTerm', '%'.mb_strtolower($searchTerm).'%');
        }

        $countQueryBuilder = clone $queryBuilder;
        $total = (int) $countQueryBuilder
            ->select('COUNT(user.id)')
            ->getQuery()
            ->getSingleScalarResult();

        /** @var list<User> $items */
        $items = $queryBuilder
            ->orderBy('user.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }
}
