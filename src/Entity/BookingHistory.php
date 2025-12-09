<?php

namespace App\Entity;

use App\Repository\BookingHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingHistoryRepository::class)]
class BookingHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $bookingId = null;

     #[ORM\Column]
    private ?int $originalBookingId = null;

    #[ORM\Column(length: 255)]
    private ?string $customerName = null;

    #[ORM\Column(length: 100)]
    private ?string $roomNumber = null;

    #[ORM\Column(type: 'json', nullable: true)]
private ?array $services = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $checkInDate = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $checkOutDate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $totalPrice = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'Checked Out';
    

        public function setOriginalBookingId(int $originalBookingId): static
    {
        $this->originalBookingId = $originalBookingId;

        return $this;
    }
        public function getOriginalBookingId(): ?int
    {
       return $this->originalBookingId;

       
    }

    public function getServices(): ?array
{
    return $this->services;
}

public function setServices(?array $services): static
{
    $this->services = $services;
    return $this;
}

    public function getId(): ?int { return $this->id; }
    public function getBookingId(): ?int { return $this->bookingId; }
    public function setBookingId(int $bookingId): self { $this->bookingId = $bookingId; return $this; }

    public function getCustomerName(): ?string { return $this->customerName; }
    public function setCustomerName(string $customerName): self { $this->customerName = $customerName; return $this; }

    public function getRoomNumber(): ?string { return $this->roomNumber; }
    public function setRoomNumber(string $roomNumber): self { $this->roomNumber = $roomNumber; return $this; }

    public function getCheckInDate(): ?\DateTimeInterface { return $this->checkInDate; }
    public function setCheckInDate(\DateTimeInterface $checkInDate): self { $this->checkInDate = $checkInDate; return $this; }

    public function getCheckOutDate(): ?\DateTimeInterface { return $this->checkOutDate; }
    public function setCheckOutDate(\DateTimeInterface $checkOutDate): self { $this->checkOutDate = $checkOutDate; return $this; }

    public function getTotalPrice(): ?string { return $this->totalPrice; }
    public function setTotalPrice(string $totalPrice): self { $this->totalPrice = $totalPrice; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}
