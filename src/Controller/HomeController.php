<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
      
        $business = [
            'name' => 'Hotel Diongco',
            'tagline' => 'Where comfort meets elegance.',
            'address' => 'Rizal Avenue, Dumaguete City, Negros Oriental, Philippines',
            'email' => 'reservations@hoteldiongco.ph',
            'phone' => '+63 969 6969 696',
            'social'=> '@HotelDiongco (Facebook & Instagram)',
            
        ];

        
 

        return $this->render('home.html.twig', [
            'business' => $business,
        ]);
    }
}



