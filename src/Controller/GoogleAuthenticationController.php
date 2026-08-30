<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\Routing\Attribute\Route;

final class GoogleAuthenticationController extends AbstractController
{
    #[Route('/connect/google', name: 'app_google_connect')]
    public function connect(ClientRegistry $clientRegistry, Request $request): Response
    {
        $authenticatedUser = $this->getUser();
        if ($authenticatedUser instanceof User && $authenticatedUser->getId() !== null) {
            $request->getSession()->set('google_identity_link_user_id', $authenticatedUser->getId());
        }
        return $clientRegistry->getClient('google')->redirect(['email', 'profile'], []);
    }
    #[Route('/connect/google/check', name: 'app_google_check')]
    public function check(): never { throw new \LogicException('Cette route est interceptée par l’authentificateur Google.'); }
}
