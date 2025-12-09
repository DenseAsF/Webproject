<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ServiceController extends AbstractController
{
    #[Route('/service', name: 'service')]
    public function index(): Response
    {
        return $this->render('services.html.twig', [
            'controller_name' => 'ServiceController',
        ]);
    }
}
