<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Points
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $totalPoints = 0;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $lastUpdated;


    #[ORM\OneToOne(inversedBy: 'points', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->lastUpdated = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalPoints(): int
    {
        return $this->totalPoints;
    }

    public function setTotalPoints(int $points): self
    {
        $this->totalPoints = $points;
        $this->lastUpdated = new \DateTime();

        return $this;
    }

    public function addPoints(int $points): self
    {
        $this->totalPoints += $points;
        $this->lastUpdated = new \DateTime();

        return $this;
    }

    public function subtractPoints(int $points): self
    {
        $this->totalPoints = max(0, $this->totalPoints - $points);
        $this->lastUpdated = new \DateTime();

        return $this;
    }

    public function getLastUpdated(): \DateTimeInterface
    {
        return $this->lastUpdated;
    }


    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }
}