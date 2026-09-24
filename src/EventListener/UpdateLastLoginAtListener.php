<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class UpdateLastLoginAtListener
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $authenticatedUser = $event->getUser();
        if (!$authenticatedUser instanceof User) {
            return;
        }

        $authenticatedUser->markLoginNow();
        $this->entityManager->flush();
    }
}
