<?php

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\ShoppingList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ShoppingListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShoppingList::class);
    }

    public function findActiveForProfile(Profile $profile): ?ShoppingList
    {
        return $this->findOneBy(['profile' => $profile, 'active' => true], ['updatedAt' => 'DESC']);
    }
}
