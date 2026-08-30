<?php

namespace App\Entity;

use App\Repository\UserIdentityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserIdentityRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_provider_identity', columns: ['provider', 'provider_user_id'])]
class UserIdentity
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 30)] private string $provider;
    #[ORM\Column(name: 'provider_user_id', length: 255)] private string $providerUserId;
    #[ORM\Column(length: 180, nullable: true)] private ?string $providerEmail = null;
    #[ORM\ManyToOne(inversedBy: 'identities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;
    #[ORM\Column] private \DateTimeImmutable $createdAt;

    public function __construct(string $provider, string $providerUserId, ?string $providerEmail = null)
    { $this->provider = $provider; $this->providerUserId = $providerUserId; $this->providerEmail = $providerEmail; $this->createdAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getProvider(): string { return $this->provider; }
    public function getProviderUserId(): string { return $this->providerUserId; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
}
