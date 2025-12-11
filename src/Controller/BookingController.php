<?php

namespace App\Controller;

use App\Repository\BookingHistoryRepository;
use App\Form\BookingType;
use App\Entity\Booking;
use App\Entity\BookingHistory;
use App\Entity\BookingService;
use App\Entity\User; 
use App\Repository\UserRepository;
use App\Repository\RoomRepository;
use App\Repository\ServiceRepository;
use App\Repository\BookingRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/booking')]
class BookingController extends AbstractController
{

    #[Route('/', name: 'booking_index', methods: ['GET'])]
    public function index(BookingRepository $bookingRepo, UserRepository $userRepo, Request $request): Response
    {
        $status = $request->query->get('status');
        $search = $request->query->get('search');
        $userId = $request->query->get('customer'); 
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');

        $qb = $bookingRepo->createQueryBuilder('b')
            ->where('b.status != :checkedOut')
            ->setParameter('checkedOut', 'Checked Out');

        if ($status && in_array($status, ['Booked', 'Checked In'])) {
            $qb->andWhere('b.status = :status')
               ->setParameter('status', $status);
        }

        if ($search) {
            $qb->andWhere('b.id LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($userId) {
            $qb->andWhere('b.user = :userId') 
               ->setParameter('userId', $userId);
        }

        if ($startDate && $endDate) {
            try {
                $start = new \DateTime($startDate);
                $end = new \DateTime($endDate);
                
                $qb->andWhere('b.checkInDate <= :endDate AND b.checkOutDate >= :startDate')
                   ->setParameter('startDate', $start)
                   ->setParameter('endDate', $end);
            } catch (\Exception $e) {
                // Date parsing error, ignore filter
            }
        }

        $qb->orderBy('b.checkInDate', 'ASC');

        $bookings = $qb->getQuery()->getResult();
        $users = $userRepo->findAll(); // Changed from $customers

        return $this->render('booking/index.html.twig', [
            'bookings' => $bookings,
            'customers' => $users, // Still passing as 'customers' for template compatibility
        ]);
    }

    #[Route('/new', name: 'booking_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em, RoomRepository $roomRepo, UserRepository $userRepo, ServiceRepository $serviceRepo, ActivityLogger $activityLogger): Response
    {
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);
      
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $account = $form->get('customerAccountNumber')->getData();
                $user = $userRepo->findOneBy(['accountNumber' => $account]); // Changed from customer
                if (!$user) {
                    $this->addFlash('error', 'User not found.');
                    return $this->redirectToRoute('booking_new');
                }
                $booking->setUser($user); // Changed from setCustomer

                $roomId = $form->get('roomId')->getData();
                if (!$roomId) {
                    $this->addFlash('error', 'Please select a room.');
                    return $this->redirectToRoute('booking_new');
                }

                $room = $roomRepo->find($roomId);
                if (!$room) {
                    $this->addFlash('error', 'Invalid room selected.');
                    return $this->redirectToRoute('booking_new');
                }
                $booking->setRoom($room);

                $total = $room->getPrice();

                $serviceIds = $request->request->all()['services'] ?? [];
                $selectedServicesPrice = 0;

                if ($serviceIds && is_array($serviceIds)) {
                    foreach ($serviceIds as $serviceId) {
                        $service = $serviceRepo->find($serviceId);
                        if ($service) {
                            $bookingService = new BookingService();
                            $bookingService->setBooking($booking);
                            $bookingService->setService($service);
                            $booking->addBookingService($bookingService);
                            $selectedServicesPrice += $service->getPrice();
                            $em->persist($bookingService);
                        }
                    }
                }

                $booking->setTotalPrice($total + $selectedServicesPrice);
                $booking->setStatus('Booked');

                $roomStatus = $em->getRepository(\App\Entity\RoomStatus::class)->findOneBy(['name' => 'Booked']);
                if ($roomStatus) {
                    $room->setStatus($roomStatus);
                }

                $em->persist($booking);
                $em->flush();

                $activityLogger->log(
                    action: 'BOOKING_CREATE',
                    entityType: 'Booking',
                    entityId: $booking->getId(),
                    description: 'Booking created for ' . $user->getUsername());

                return $this->redirectToRoute('booking_index');

            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
                return $this->redirectToRoute('booking_new');
            }
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                error_log("FORM ERROR: " . $error->getMessage());
            }
            $response = new Response(null, 422);
            return $this->render('booking/new.html.twig', [
                'form' => $form->createView(),
                'services' => $serviceRepo->findAll(),
            ], $response);
        }

        return $this->render('booking/new.html.twig', [
            'form' => $form->createView(),
            'services' => $serviceRepo->findAll(),
        ]);
    }

    #[Route('/available-rooms-debug', name: 'booking_available_rooms_debug', methods: ['GET'])]
    public function availableRoomsDebug(Request $request, RoomRepository $roomRepo): Response
    {
        $checkInStr = $request->query->get('checkIn');
        $checkOutStr = $request->query->get('checkOut');

        error_log("DEBUG: Received checkIn: " . $checkInStr);
        error_log("DEBUG: Received checkOut: " . $checkOutStr);

        if (!$checkInStr || !$checkOutStr) {
            error_log("DEBUG: Missing dates");
            return $this->json(['rooms' => [], 'debug' => 'Missing dates']);
        }

        try {
            $checkIn = new \DateTime($checkInStr);
            $checkOut = new \DateTime($checkOutStr);
            error_log("DEBUG: Parsed dates successfully");
        } catch (\Exception $e) {
            error_log("DEBUG: Date parsing error: " . $e->getMessage());
            return $this->json(['rooms' => [], 'debug' => 'Date parsing error']);
        }

        $allAvailableRooms = $roomRepo->createQueryBuilder('r')
            ->join('r.status', 's')
            ->where('s.name = :status')
            ->setParameter('status', 'Available')
            ->getQuery()
            ->getResult();

        error_log("DEBUG: Total available rooms: " . count($allAvailableRooms));

        $availableRooms = $roomRepo->createQueryBuilder('r')
            ->join('r.status', 's')
            ->where('s.name = :status')
            ->setParameter('status', 'Available')
            ->andWhere('r.id NOT IN (
                SELECT IDENTITY(b.room) FROM App\Entity\Booking b
                WHERE b.status IN (:bookedStatuses)
                AND b.checkInDate < :checkOut
                AND b.checkOutDate > :checkIn
            )')
            ->setParameter('checkIn', $checkIn)
            ->setParameter('checkOut', $checkOut)
            ->setParameter('bookedStatuses', ['Booked','Checked In'])
            ->getQuery()
            ->getResult();

        error_log("DEBUG: Available rooms after date filter: " . count($availableRooms));

        $rooms = [];
        foreach ($availableRooms as $room) {
            $rooms[] = [
                'id' => $room->getId(),
                'label' => 'Room ' . $room->getRoomNumber() . ' - ₱' . $room->getPrice(),
            ];
        }

        $debugInfo = [
            'received_checkIn' => $checkInStr,
            'received_checkOut' => $checkOutStr,
            'total_available_rooms' => count($allAvailableRooms),
            'filtered_available_rooms' => count($availableRooms),
            'query_executed' => true
        ];

        return $this->json(['rooms' => $rooms, 'debug' => $debugInfo]);
    }

    #[Route('/test-rooms', name: 'booking_test_rooms', methods: ['GET'])]
    public function testRooms(): Response
    {
        return $this->json([
            'message' => 'Test route is working',
            'rooms' => [
                ['id' => 1, 'label' => 'Test Room 101 - ₱1000'],
                ['id' => 2, 'label' => 'Test Room 102 - ₱1200']
            ]
        ]);
    }

    #[Route('/available-rooms', name: 'booking_available_rooms', methods: ['GET'])]
    public function availableRooms(Request $request, RoomRepository $roomRepo): Response
    {
        $checkInStr = $request->query->get('checkIn');
        $checkOutStr = $request->query->get('checkOut');

        if (!$checkInStr || !$checkOutStr) {
            return $this->json(['rooms' => []]);
        }

        try {
            $checkIn = new \DateTime($checkInStr);
            $checkOut = new \DateTime($checkOutStr);
        } catch (\Exception $e) {
            return $this->json(['rooms' => []]);
        }

        $availableRooms = $roomRepo->createQueryBuilder('r')
            ->join('r.status', 's')
            ->where('s.name = :status')
            ->setParameter('status', 'Available')
            ->andWhere('r.id NOT IN (
                SELECT IDENTITY(b.room) FROM App\Entity\Booking b
                WHERE b.status IN (:bookedStatuses)
                AND b.checkInDate < :checkOut
                AND b.checkOutDate > :checkIn
            )')
            ->setParameter('checkIn', $checkIn)
            ->setParameter('checkOut', $checkOut)
            ->setParameter('bookedStatuses', ['Booked','Checked In'])
            ->getQuery()
            ->getResult();

        $rooms = [];
        foreach ($availableRooms as $room) {
            $rooms[] = [
                'id' => $room->getId(),
                'label' => 'Room ' . $room->getRoomNumber() . ' - ₱' . $room->getPrice(),
            ];
        }

        return $this->json(['rooms' => $rooms]);
    }

    #[Route('/{id}/checkout', name: 'booking_checkout', methods: ['POST'])]
    public function checkout(Request $request, Booking $booking, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('checkout'.$booking->getId(), $request->request->get('_token'))) {
            $history = new BookingHistory();
            
            $history->setOriginalBookingId($booking->getId());
            $history->setBookingId($booking->getId());
            $history->setCustomerName($booking->getUser()->getName()); // Changed from getCustomer
            $history->setRoomNumber($booking->getRoom()->getRoomNumber());
            $history->setCheckInDate($booking->getCheckInDate());
            $history->setCheckOutDate($booking->getCheckOutDate());
            $history->setTotalPrice($booking->getTotalPrice());
            $history->setStatus('Checked Out');

            $servicesData = [];
            foreach ($booking->getBookingServices() as $bookingService) {
                $servicesData[] = [
                    'name' => $bookingService->getService()->getName(),
                    'price' => $bookingService->getService()->getPrice()
                ];
            }
            $history->setServices($servicesData);

            $em->persist($history);
            $em->remove($booking);
            $em->flush();

            $activityLogger->log(
                action: 'BOOKING_CHECKOUT',
                entityType: 'Booking',
                entityId: $history->getId(),
                description: 'Checked out booking for ' . $history->getCustomerName()
            );
        }

        return $this->redirectToRoute('booking_index');
    }

    #[Route('/history/{id}/delete', name: 'booking_history_delete', methods: ['POST'])]
    public function deleteHistory(BookingHistory $history, Request $request, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('delete' . $history->getId(), $request->request->get('_token'))) {
            $customerName = $history->getCustomerName();
            $em->remove($history);
            $em->flush();

            $activityLogger->log(
                action: 'BOOKING_HISTORY_DELETE',
                entityType: 'BookingHistory',
                entityId: $history->getId(),
                description: 'Deleted booking history for ' . $customerName
            );
        }

        return $this->redirectToRoute('booking_history');
    }

    #[Route('/history/{id}/edit', name: 'booking_history_edit', methods: ['GET','POST'])]
    public function editHistory(BookingHistory $history, Request $request, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        $form = $this->createFormBuilder($history)
            ->add('customerName', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->add('roomNumber', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->add('checkInDate', \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('checkOutDate', \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('totalPrice', \Symfony\Component\Form\Extension\Core\Type\NumberType::class)
            ->add('status', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $activityLogger->log(
                action: 'BOOKING_HISTORY_EDIT',
                entityType: 'BookingHistory',
                entityId: $history->getId(),
                description: 'Edited booking history for ' . $history->getCustomerName()
            );

            $this->addFlash('success', 'Booking history updated successfully!');
            return $this->redirectToRoute('booking_history_show', ['id' => $history->getId()]);
        }

        return $this->render('booking/historyEdit.html.twig', [
            'form' => $form->createView(),
            'booking_history' => $history,
        ]);
    }

    #[Route('/search/customer', name: 'booking_search_customer', methods: ['GET'])]
    public function searchCustomer(Request $request, UserRepository $userRepo): Response // Changed to UserRepository
    {
        $term = strtoupper(trim($request->query->get('term', '')));
        $users = $userRepo->createQueryBuilder('u') // Changed from 'c' to 'u'
            ->where('u.accountNumber LIKE :term')
            ->setParameter('term', $term . '%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($users as $user) { // Changed from $cust to $user
            $results[] = [
                'label' => $user->getAccountNumber() . ' - ' . $user->getName(),
                'value' => $user->getAccountNumber(),
            ];
        }

        return $this->json($results);
    }

    #[Route('/history', name: 'booking_history', methods: ['GET'])]
    public function history(BookingHistoryRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search');
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        
        $qb = $repo->createQueryBuilder('bh');

        if ($search) {
            $qb->andWhere('bh.originalBookingId LIKE :search OR bh.id LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($startDate && $endDate) {
            try {
                $start = new \DateTime($startDate);
                $end = new \DateTime($endDate);
                
                $qb->andWhere('bh.checkInDate >= :startDate AND bh.checkOutDate <= :endDate')
                   ->setParameter('startDate', $start)
                   ->setParameter('endDate', $end);
            } catch (\Exception $e) {
                // Ignore date errors
            }
        }

        $qb->orderBy('bh.id', 'DESC');

        $bookings = $qb->getQuery()->getResult();

        return $this->render('booking/history.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/{id}/checkin', name: 'booking_checkin', methods: ['POST'])]
    public function checkIn(Request $request, Booking $booking, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('checkin' . $booking->getId(), $request->request->get('_token'))) {
            $booking->setStatus('Checked In');
            $em->flush();

            $activityLogger->log(
                action: 'BOOKING_CHECKIN',
                entityType: 'Booking',
                entityId: $booking->getId(),
                description: 'Checked in booking for ' . $booking->getUser()->getUsername()
            );
        }

        return $this->redirectToRoute('booking_index');
    }

    #[Route('/{id}/edit', name: 'booking_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Booking $booking, EntityManagerInterface $em, RoomRepository $roomRepo, UserRepository $userRepo, ServiceRepository $serviceRepo, ActivityLogger $activityLogger): Response
    {
        $originalRoom = $booking->getRoom();
        
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $account = $form->get('customerAccountNumber')->getData();
                $user = $userRepo->findOneBy(['accountNumber' => $account]); // Changed from customer
                if (!$user) {
                    $this->addFlash('error', 'User not found.');
                    return $this->redirectToRoute('booking_edit', ['id' => $booking->getId()]);
                }
                $booking->setUser($user); // Changed from setCustomer

                $roomId = $form->get('roomId')->getData();
                if (!$roomId) {
                    $this->addFlash('error', 'Please select a room.');
                    return $this->redirectToRoute('booking_edit', ['id' => $booking->getId()]);
                }

                $room = $roomRepo->find($roomId);
                if (!$room) {
                    $this->addFlash('error', 'Invalid room selected.');
                    return $this->redirectToRoute('booking_edit', ['id' => $booking->getId()]);
                }

                if ($originalRoom->getId() !== $room->getId()) {
                    $availableStatus = $em->getRepository(\App\Entity\RoomStatus::class)->findOneBy(['name' => 'Available']);
                    if ($availableStatus) {
                        $originalRoom->setStatus($availableStatus);
                    }
            
                    $bookedStatus = $em->getRepository(\App\Entity\RoomStatus::class)->findOneBy(['name' => 'Booked']);
                    if ($bookedStatus) {
                        $room->setStatus($bookedStatus);
                    }
                }
                
                $booking->setRoom($room);

                $total = $room->getPrice();

                $booking->getBookingServices()->clear();
                
                $serviceIds = $request->request->all()['services'] ?? [];
                $selectedServicesPrice = 0;

                if ($serviceIds && is_array($serviceIds)) {
                    foreach ($serviceIds as $serviceId) {
                        $service = $serviceRepo->find($serviceId);
                        if ($service) {
                            $bookingService = new BookingService();
                            $bookingService->setBooking($booking);
                            $bookingService->setService($service);
                            $booking->addBookingService($bookingService);
                            $selectedServicesPrice += $service->getPrice();
                            $em->persist($bookingService);
                        }
                    }
                }

                $booking->setTotalPrice($total + $selectedServicesPrice);

                $em->flush();

// update sa booking

                $activityLogger->log(
                    action: 'BOOKING_UPDATE',
                    entityType: 'Booking',
                    entityId: $booking->getId(),
                    description: 'Booking updated'
                );

                return $this->redirectToRoute('booking_show', ['id' => $booking->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
                return $this->redirectToRoute('booking_edit', ['id' => $booking->getId()]);
            }
        }

        $form->get('customerAccountNumber')->setData($booking->getUser()->getAccountNumber()); // Changed from getCustomer
        $form->get('roomId')->setData($booking->getRoom()->getId());

        return $this->render('booking/edit.html.twig', [
            'booking' => $booking,
            'form' => $form->createView(),
            'services' => $serviceRepo->findAll(),
        ]);
    }

    #[Route('/{id}/delete', name: 'booking_delete', methods: ['POST'])]
    public function delete(Request $request, Booking $booking, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('delete'.$booking->getId(), $request->request->get('_token'))) {
            $bookingId = $booking->getId();
            $em->remove($booking);
            $em->flush();

            // Log booking deletion (staff deletes a booking)
            $activityLogger->log(
                action: 'BOOKING_DELETE',
                entityType: 'Booking',
                entityId: $bookingId,
                description: 'Booking deleted (ID ' . $bookingId . ')'
            );
        }

        return $this->redirectToRoute('booking_index');
    }

    #[Route('/history/{id}', name: 'booking_history_show', methods: ['GET'])]
    public function showHistory(int $id, BookingHistoryRepository $bookingHistoryRepo): Response
    {
        $bookingHistory = $bookingHistoryRepo->find($id);
        
        if (!$bookingHistory) {
            throw $this->createNotFoundException('Booking history not found');
        }

        return $this->render('booking/historyShow.html.twig', [
            'booking_history' => $bookingHistory,
        ]);
    }

    #[Route('/{id}', name: 'booking_show', methods: ['GET'])]
    public function show(Booking $booking): Response
    {
        return $this->render('booking/show.html.twig', [
            'booking' => $booking,
        ]);
    }
}