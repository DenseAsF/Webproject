<?php

namespace App\Repository;

use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Room>
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    //    /**
    //     * @return Room[] Returns an array of Room objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Room
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
public function findAvailableRooms(\DateTimeInterface $checkIn, \DateTimeInterface $checkOut)
{
    $qb = $this->createQueryBuilder('r');

    $qb->andWhere('r.id NOT IN (
        SELECT IDENTITY(b.room)
        FROM App\Entity\Booking b
        WHERE b.checkInDate < :checkOut
          AND b.checkOutDate > :checkIn
          AND b.status IN (:activeStatuses)
    )')
    ->setParameter('checkIn', $checkIn)
    ->setParameter('checkOut', $checkOut)
    ->setParameter('activeStatuses', ['Booked', 'Checked In'])
    ->orderBy('r.roomNumber', 'ASC');

    return $qb->getQuery()->getResult();
}

   public function findByFilters(?string $availability = null, $roomTypeId = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.status', 's')
            ->leftJoin('r.roomType', 'rt')
            ->orderBy('r.roomNumber', 'ASC');

        if ($availability === 'available') {
            $qb->andWhere('s.name = :availableStatus')
               ->setParameter('availableStatus', 'Available');
        } elseif ($availability === 'unavailable') {
            $qb->andWhere('s.name != :availableStatus')
               ->setParameter('availableStatus', 'Available');
        }

        if ($roomTypeId) {
            $roomTypeId = is_string($roomTypeId) ? (int)$roomTypeId : $roomTypeId;
            $qb->andWhere('rt.id = :roomTypeId')
               ->setParameter('roomTypeId', $roomTypeId);
        }

        // Add search functionality for room number
        if ($search) {
            $qb->andWhere('r.roomNumber LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
