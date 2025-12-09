<?php

namespace App\Entity;

use App\Repository\BookingRoomRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRoomRepository::class)]
class BookingRoom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Booking::class, inversedBy: 'bookingRooms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $booking = null;

    // FIX THIS LINE: Add targetEntity and inversedBy
    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'bookingRooms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Room $room = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): self
    {
        $this->booking = $booking;
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
}