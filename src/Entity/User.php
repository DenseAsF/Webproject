<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\UniqueConstraint(columns: ['username'])]
#[ORM\UniqueConstraint(columns: ['email'])]
#[ORM\UniqueConstraint(columns: ['account_number'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Username is required.')]
    #[Assert\Length(min: 3, max: 180)]
    private string $username;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private string $password;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    private string $email;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Phone number is required.')]
    #[Assert\Regex(pattern: '/^09\d{9}$/', message: 'Phone number must start with 09 and contain exactly 11 digits.')]
    private string $phone;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Name is required.')]
    private string $name;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: 'Age is required.')]
    #[Assert\Range(min: 1, max: 120)]
    private int $age;

    #[ORM\Column(name: 'account_number', length: 50, unique: true)]
    private string $accountNumber;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Points::class, cascade: ["persist","remove"])]
    private ?Points $points = null;

    private ?string $plainPassword = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->roles = ['ROLE_USER'];
    }

    public function getId(): ?int { return $this->id; }

    public function getUserIdentifier(): string { return $this->username; }

    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }

    public function getRoles(): array {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }
        return array_unique($roles);
    }

    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function eraseCredentials(): void { $this->plainPassword = null; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getPhone(): string { return $this->phone; }
    public function setPhone(string $phone): self { $this->phone = $phone; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getAge(): int { return $this->age; }
    public function setAge(int $age): self { $this->age = $age; return $this; }

    public function getAccountNumber(): string { return $this->accountNumber; }
    public function setAccountNumber(string $accountNumber): self { $this->accountNumber = $accountNumber; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
    }

    public function getPoints(): ?Points { return $this->points; }

    public function setPoints(?Points $points): self {
        $this->points = $points;
        if ($points !== null && $points->getUser() !== $this) {
            $points->setUser($this);
        }
        return $this;
    }

    public function getPlainPassword(): ?string { return $this->plainPassword; }
    public function setPlainPassword(?string $plainPassword): self { $this->plainPassword = $plainPassword; return $this; }

    public function __toString(): string { return $this->username ?? ''; }
}
