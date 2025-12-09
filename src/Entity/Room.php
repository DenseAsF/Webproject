<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
   #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 100, nullable: false, unique: true)]
    #[Assert\NotBlank(message: 'Room number is required.')]
    #[Assert\Regex(
        pattern: '/^\d+$/',
        message: 'Room number must contain only numbers.'
    )]
    private ?string $roomNumber = null;

   
    #[ORM\Column]
    #[Assert\NotBlank(message: 'Please enter the maximum number of people.')]
    #[Assert\Range(
        min: 1,
        max: 8,
        notInRangeMessage: 'The number of people must be between {{ min }} and {{ max }}.'
    )]
    private ?int $maxPeople = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Price is required.')]
    #[Assert\Positive(message: 'Price must be a positive number.')]
    private ?string $price = null;

    #[ORM\ManyToOne(inversedBy: 'rooms')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please select a room type.')]
    private ?RoomType $roomType = null;

    #[ORM\ManyToOne(inversedBy: 'rooms')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please select a room status.')]
    private ?RoomStatus $status = null;

    /**
     * @var Collection<int, BookingRoom>
     */
    #[ORM\OneToMany(targetEntity: BookingRoom::class, mappedBy: 'room')]
    private Collection $bookingRooms;

    public function __construct()
    {
        $this->bookingRooms = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoomNumber(): ?string
    {
        return $this->roomNumber;
    }

    public function setRoomNumber(?string $roomNumber): static
    {
        $this->roomNumber = $roomNumber;

        return $this;
    }

    public function getMaxPeople(): ?int
    {
        return $this->maxPeople;
    }

    public function setMaxPeople(int $maxPeople): static
    {
        $this->maxPeople = $maxPeople;

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

    public function getRoomType(): ?RoomType
    {
        return $this->roomType;
    }

    public function setRoomType(?RoomType $roomType): static
    {
        $this->roomType = $roomType;

        return $this;
    }

    public function getStatus(): ?RoomStatus
    {
        return $this->status;
    }

    public function setStatus(?RoomStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, BookingRoom>
     */
    public function getBookingRooms(): Collection
    {
        return $this->bookingRooms;
    }

    public function addBookingRoom(BookingRoom $bookingRoom): static
    {
        if (!$this->bookingRooms->contains($bookingRoom)) {
            $this->bookingRooms->add($bookingRoom);
            $bookingRoom->setRoom($this);
        }

        return $this;
    }

    public function removeBookingRoom(BookingRoom $bookingRoom): static
    {
        if ($this->bookingRooms->removeElement($bookingRoom)) {
            // set the owning side to null (unless already changed)
            if ($bookingRoom->getRoom() === $this) {
                $bookingRoom->setRoom(null);
            }
        }

        return $this;
    }
}
