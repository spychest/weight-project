<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
#[UniqueEntity(fields: ['email'], message: 'Un compte utilise déjà cette adresse e-mail.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column]
    private bool $emailVerified = false;

    #[ORM\Column]
    private bool $darkModeEnabled = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Profile::class)]
    private ?Profile $profile = null;

    /** @var Collection<int, UserIdentity> */
    #[ORM\OneToMany(targetEntity: UserIdentity::class, mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    private Collection $identities;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->identities = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): static { $this->email = mb_strtolower(trim($email)); return $this; }
    public function getUserIdentifier(): string { return $this->email; }
    public function getRoles(): array { return array_values(array_unique([...$this->roles, 'ROLE_USER'])); }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(?string $password): static { $this->password = $password; return $this; }
    public function eraseCredentials(): void {}
    public function isEmailVerified(): bool { return $this->emailVerified; }
    public function setEmailVerified(bool $emailVerified): static { $this->emailVerified = $emailVerified; return $this; }
    public function isDarkModeEnabled(): bool { return $this->darkModeEnabled; }
    public function setDarkModeEnabled(bool $darkModeEnabled): static { $this->darkModeEnabled = $darkModeEnabled; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getLastLoginAt(): ?\DateTimeImmutable { return $this->lastLoginAt; }
    public function markLoginNow(): void { $this->lastLoginAt = new \DateTimeImmutable(); }
    public function getProfile(): ?Profile { return $this->profile; }
    public function setProfile(?Profile $profile): static { $this->profile = $profile; if ($profile?->getUser() !== $this) { $profile?->setUser($this); } return $this; }
    /** @return Collection<int, UserIdentity> */
    public function getIdentities(): Collection { return $this->identities; }
    public function addIdentity(UserIdentity $identity): static { if (!$this->identities->contains($identity)) { $this->identities->add($identity); $identity->setUser($this); } return $this; }
}
