<?php

namespace App\Service;

use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ProfileRepository;
use Symfony\Bundle\SecurityBundle\Security;

final class CurrentUserProfileProvider
{
    public function __construct(private readonly Security $security, private readonly ProfileRepository $profileRepository) {}
    public function getProfile(): ?Profile
    {
        $authenticatedUser = $this->security->getUser();
        return $authenticatedUser instanceof User ? $this->profileRepository->findForUser($authenticatedUser) : null;
    }
    public function getRequiredProfile(): Profile
    {
        return $this->getProfile() ?? throw new \LogicException('Le compte connecté ne possède pas encore de profil.');
    }
    public function ownsProfile(?Profile $profile): bool { return $profile !== null && $profile === $this->getProfile(); }
}
