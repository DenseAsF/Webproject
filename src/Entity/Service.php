<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    /**
     * @var Collection<int, BookingService>
     */
    #[ORM\OneToMany(targetEntity: BookingService::class, mappedBy: 'service')]
    private Collection $bookingServices;

    public function __construct()
    {
        $this->bookingServices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, BookingService>
     */
    public function getBookingServices(): Collection
    {
        return $this->bookingServices;
    }

    public function addBookingService(BookingService $bookingService): static
    {
        if (!$this->bookingServices->contains($bookingService)) {
            $this->bookingServices->add($bookingService);
            $bookingService->setService($this);
        }

        return $this;
    }

    public function removeBookingService(BookingService $bookingService): static
    {
        if ($this->bookingServices->removeElement($bookingService)) {
            
            if ($bookingService->getService() === $this) {
                $bookingService->setService(null);
            }
        }

        return $this;
    }
}
