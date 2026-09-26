<?php

namespace App\DTO\Admin;

use App\Entity\User;

final readonly class AdminUserData implements \JsonSerializable
{
    /**
     * @param list<string> $roles
     * @param list<string> $authenticationMethods
     */
    public function __construct(
        public int $id,
        public string $displayName,
        public ?string $avatarUrl,
        public array $roles,
        public bool $hasProfile,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $lastLoginAt,
        public bool $suspended,
        public ?\DateTimeImmutable $suspendedAt,
        public ?string $suspensionReason,
        public array $authenticationMethods,
        public int $publicRecipeCount,
    ) {
    }

    public static function fromUser(User $user): self
    {
        $profile = $user->getProfile();
        $avatarUrl = $profile?->getAvatarFilename() !== null
            ? '/uploads/profiles/'.rawurlencode($profile->getAvatarFilename())
            : $profile?->getGoogleAvatarUrl();
        $authenticationMethods = [];

        if ($user->getPassword() !== null) {
            $authenticationMethods[] = 'Mot de passe';
        }
        foreach ($user->getIdentities() as $identity) {
            $providerLabel = match ($identity->getProvider()) {
                'google' => 'Google',
                default => ucfirst($identity->getProvider()),
            };
            if (!in_array($providerLabel, $authenticationMethods, true)) {
                $authenticationMethods[] = $providerLabel;
            }
        }

        return new self(
            id: $user->getId() ?? throw new \LogicException('An administered user must be persisted.'),
            displayName: $profile?->getDisplayName() ?? 'Profil non créé',
            avatarUrl: $avatarUrl,
            roles: $user->getRoles(),
            hasProfile: $profile !== null,
            createdAt: $user->getCreatedAt(),
            lastLoginAt: $user->getLastLoginAt(),
            suspended: $user->isSuspended(),
            suspendedAt: $user->getSuspendedAt(),
            suspensionReason: $user->getSuspensionReason(),
            authenticationMethods: $authenticationMethods,
            publicRecipeCount: $profile?->getRecipes()->count() ?? 0,
        );
    }

    /**
     * @return array{
     *     id: int,
     *     displayName: string,
     *     avatarUrl: string|null,
     *     roles: list<string>,
     *     hasProfile: bool,
     *     createdAt: string,
     *     lastLoginAt: string|null,
     *     suspended: bool,
     *     suspendedAt: string|null,
     *     suspensionReason: string|null,
     *     authenticationMethods: list<string>,
     *     publicRecipeCount: int
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'displayName' => $this->displayName,
            'avatarUrl' => $this->avatarUrl,
            'roles' => $this->roles,
            'hasProfile' => $this->hasProfile,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'lastLoginAt' => $this->lastLoginAt?->format(DATE_ATOM),
            'suspended' => $this->suspended,
            'suspendedAt' => $this->suspendedAt?->format(DATE_ATOM),
            'suspensionReason' => $this->suspensionReason,
            'authenticationMethods' => $this->authenticationMethods,
            'publicRecipeCount' => $this->publicRecipeCount,
        ];
    }
}
