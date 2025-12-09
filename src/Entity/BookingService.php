<?php

namespace App\Entity;

use App\Repository\BookingServiceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingServiceRepository::class)]
class BookingService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Booking::class, inversedBy: 'bookingServices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $booking = null;

    // FIX THIS LINE: Add inversedBy attribute
    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'bookingServices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }
}