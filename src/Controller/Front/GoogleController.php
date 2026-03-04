<?php

namespace App\Controller\Front;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GoogleController
 *
 * Route 1 — /connect/google       → redirige vers Google OAuth
 * Route 2 — /connect/google/check → callback géré par GoogleAuthenticator
 */
class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    /**
     * La logique de cette route est entièrement gérée par GoogleAuthenticator.
     * Ce controller ne sera jamais exécuté directement.
     */
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry): Response
    {
        // Géré par le security authenticator — jamais atteint normalement
        return new Response('', Response::HTTP_OK);
    }
}
