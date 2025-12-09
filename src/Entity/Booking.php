<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: BookingService::class, mappedBy: 'booking', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $bookingServices;

    // ADD THIS: BookingRooms relationship
    #[ORM\OneToMany(targetEntity: BookingRoom::class, mappedBy: 'booking', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $bookingRooms;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable:false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Room $room = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $checkInDate = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $checkOutDate = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'Booked';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $totalPrice = null;

    public function __construct()
    {
        $this->bookingServices = new ArrayCollection();
        $this->bookingRooms = new ArrayCollection(); // ADD THIS
    }

    // BookingServices methods
    public function getBookingServices(): Collection
    {
        return $this->bookingServices;
    }

    public function addBookingService(BookingService $bookingService): self
    {
        if (!$this->bookingServices->contains($bookingService)) {
            $this->bookingServices->add($bookingService);
            $bookingService->setBooking($this);
        }
        return $this;
    }

    public function removeBookingService(BookingService $bookingService): self
    {
        if ($this->bookingServices->removeElement($bookingService)) {
            if ($bookingService->getBooking() === $this) {
                $bookingService->setBooking(null);
            }
        }
        return $this;
    }

    // ADD THESE: BookingRooms methods
    public function getBookingRooms(): Collection
    {
        return $this->bookingRooms;
    }

    public function addBookingRoom(BookingRoom $bookingRoom): self
    {
        if (!$this->bookingRooms->contains($bookingRoom)) {
            $this->bookingRooms->add($bookingRoom);
            $bookingRoom->setBooking($this);
        }
        return $this;
    }

    public function removeBookingRoom(BookingRoom $bookingRoom): self
    {
        if ($this->bookingRooms->removeElement($bookingRoom)) {
            if ($bookingRoom->getBooking() === $this) {
                $bookingRoom->setBooking(null);
            }
        }
        return $this;
    }

    // Other getters/setters
    public function getId(): ?int
    {
        return $this->id;
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

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): self
    {
        $this->room = $room;
        return $this;
    }

    public function getCheckInDate(): ?\DateTimeInterface
    {
        return $this->checkInDate;
    }

    public function setCheckInDate(?\DateTimeInterface $checkInDate): self
    {
        $this->checkInDate = $checkInDate;
        return $this;
    }

    public function getCheckOutDate(): ?\DateTimeInterface
    {
        return $this->checkOutDate;
    }

    public function setCheckOutDate(?\DateTimeInterface $checkOutDate): self
    {
        $this->checkOutDate = $checkOutDate;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?string $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }
}