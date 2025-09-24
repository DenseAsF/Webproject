<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AboutController extends AbstractController
{
    #[Route('/about', name: 'about')]
    public function index(): Response
    {
       
        $hotelInfo = [
            'name' => 'Hotel Diongco',
            'founded' => 2010,
            'tagline' => 'Where Luxury Meets Comfort',
            'mission' => '  Our mission is to provide every guest with a luxurious and unforgettable experience, combining elegant accommodations, personalized service, and world-class amenities. 
  We strive to create an atmosphere of comfort, sophistication, and warmth, where every detail is designed to exceed expectations and leave lasting memories. 
  From business travelers to vacationers, we are dedicated to ensuring that every stay is exceptional and every guest feels valued.
.',
            'vision' => ' Our vision is to be the premier hotel in Dumaguete City, recognized for excellence in hospitality and innovation in guest services. 
  We aim to set the standard for luxury and comfort in the region, creating a space where guests feel inspired, relaxed, and cared for. 
  Through continuous improvement, attention to detail, and a commitment to sustainability and community, we aspire to leave a positive impact on every visitor and become the first choice for travelers seeking an exceptional experience.
',
        ];

  $business = [
    'name' => 'Hotel Diongco',
    'tagline' => 'Where comfort meets elegance.',
    'address' => 'Rizal Avenue, Dumaguete City, Negros Oriental, Philippines',
    'email' => 'reservations@hoteldiongco.ph',
    'phone' => '+63 969 6969 696',
    'social'=> '@HotelDiongco (Facebook & Instagram)',
];

return $this->render('about.html.twig', [
    'hotel' => $hotelInfo,
    'business' => $business,
]);
    }
}