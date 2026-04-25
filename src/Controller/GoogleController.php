<?php
// src/Controller/GoogleController.php
namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'app_google_connect')]
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        // Redirects the user to Google's login page
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    // This route is the callback — Authenticator handles it.
    #[Route('/connect/google/check', name: 'app_google_check')]
    public function connectCheckAction(): Response
    {
        // This should be handled by the authenticator, but if reached, redirect to login
        return $this->redirectToRoute('app_login');
    }
}
