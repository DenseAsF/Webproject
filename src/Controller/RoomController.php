<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomForm;
use App\Repository\RoomRepository;
use App\Repository\RoomTypeRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/room')]
class RoomController extends AbstractController
{
    private $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    #[Route(name: 'room_index', methods: ['GET'])]
    public function index(
        Request $request, 
        RoomRepository $roomRepository,
        RoomTypeRepository $roomTypeRepository
    ): Response {
        $availability = $request->query->get('availability');
        $roomTypeId = $request->query->get('roomType');
        $search = $request->query->get('search'); // Get search parameter

        // Pass all parameters including search to the repository
        $rooms = $roomRepository->findByFilters($availability, $roomTypeId, $search);

        $roomTypes = $roomTypeRepository->findAll();

        return $this->render('room/index.html.twig', [
            'rooms' => $rooms,
            'roomTypes' => $roomTypes,
        ]);
    }

    // ... rest of your controller methods remain the same
    #[Route('/new', name: 'room_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, RoomRepository $roomRepository): Response
    {
        $room = new Room();
        $form = $this->createForm(RoomForm::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $roomNumber = $form->get('roomNumber')->getData();

            // Custom duplicate check
            if ($roomRepository->findOneBy(['roomNumber' => $roomNumber])) {
                $form->get('roomNumber')->addError(new \Symfony\Component\Form\FormError('This room number already exists.'));
            }

            if ($form->isValid()) {
                $entityManager->persist($room);
                $entityManager->flush();

                $this->activityLogger->log(
                    action: 'ROOM_CREATE',
                    entityType: 'Room',
                    entityId: $room->getId(),
                    description: 'Created room ' . $room->getRoomNumber()
                );

                return $this->redirectToRoute('room_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('room/new.html.twig', [
            'room' => $room,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'room_show', methods: ['GET'])]
    public function show(Room $room): Response
    {
        return $this->render('room/show.html.twig', [
            'room' => $room,
        ]);
    }

    #[Route('/{id}/edit', name: 'room_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Room $room, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RoomForm::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->activityLogger->log(
                action: 'ROOM_EDIT',
                entityType: 'Room',
                entityId: $room->getId(),
                description: 'Edited room ' . $room->getRoomNumber()
            );

            return $this->redirectToRoute('room_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('room/edit.html.twig', [
            'room' => $room,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'room_delete', methods: ['POST'])]
    public function delete(Request $request, Room $room, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$room->getId(), $request->getPayload()->getString('_token'))) {
            $roomNumber = $room->getRoomNumber();
            $entityManager->remove($room);
            $entityManager->flush();

            $this->activityLogger->log(
                action: 'ROOM_DELETE',
                entityType: 'Room',
                entityId: $room->getId(),
                description: 'Deleted room ' . $roomNumber
            );
        }

        return $this->redirectToRoute('room_index', [], Response::HTTP_SEE_OTHER);
    }
}