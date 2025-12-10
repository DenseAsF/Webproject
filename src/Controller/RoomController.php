<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomForm;
use App\Repository\RoomRepository;
use App\Repository\RoomTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/room')]
class RoomController extends AbstractController
{
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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $room = new Room();
        $form = $this->createForm(RoomForm::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($room);
            $entityManager->flush();

            return $this->redirectToRoute('room_index', [], Response::HTTP_SEE_OTHER);
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
            $entityManager->remove($room);
            $entityManager->flush();
        }

        return $this->redirectToRoute('room_index', [], Response::HTTP_SEE_OTHER);
    }
}