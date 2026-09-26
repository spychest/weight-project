<?php

namespace App\Entity;

use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PushSubscriptionRepository::class)]
#[ORM\Table(name: 'push_subscription')]
#[ORM\UniqueConstraint(name: 'uniq_push_subscription_endpoint_hash', columns: ['endpoint_hash'])]
class PushSubscription
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'pushSubscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'text')]
    private string $endpoint = '';

    #[ORM\Column(length: 64)]
    private string $endpointHash = '';

    #[ORM\Column(length: 255)]
    private string $publicKey = '';

    #[ORM\Column(length: 255)]
    private string $authenticationToken = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $contentEncoding = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getEndpoint(): string { return $this->endpoint; }
    public function setEndpoint(string $endpoint): static
    {
        $this->endpoint = $endpoint;
        $this->endpointHash = hash('sha256', $endpoint);

        return $this;
    }
    public function getEndpointHash(): string { return $this->endpointHash; }
    public function getPublicKey(): string { return $this->publicKey; }
    public function setPublicKey(string $publicKey): static { $this->publicKey = $publicKey; return $this; }
    public function getAuthenticationToken(): string { return $this->authenticationToken; }
    public function setAuthenticationToken(string $authenticationToken): static { $this->authenticationToken = $authenticationToken; return $this; }
    public function getContentEncoding(): ?string { return $this->contentEncoding; }
    public function setContentEncoding(?string $contentEncoding): static { $this->contentEncoding = $contentEncoding; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
