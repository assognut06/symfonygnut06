<?php

namespace App\Controller;

use App\Security\OAuth\OAuthFlowManager;
use App\Security\OAuth\OAuthFlowPurpose;
use App\Security\OAuth\OAuthProvider;
use App\Entity\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start', methods: ['GET'])]
    public function connect(Request $request, ClientRegistry $clientRegistry, OAuthFlowManager $flowManager): RedirectResponse
    {
        $flow = $flowManager->start($request, OAuthProvider::Google);
        if ($this->getUser() instanceof User && $flow['purpose'] === OAuthFlowPurpose::Login) {
            $flowManager->clearFlow($request, OAuthProvider::Google);
            $this->addFlash('error', 'Démarrez une liaison depuis votre profil pour ajouter Google.');
            return $this->redirectToRoute('app_profil');
        }

        $options = ['nonce' => $flow['nonce']];
        if ($flow['purpose'] === OAuthFlowPurpose::Reauthenticate) {
            $options['max_age'] = 0;
            $options['prompt'] = 'select_account';
        }

        return $clientRegistry->getClient('google')->redirect(['openid', 'profile', 'email'], $options);
    }

    #[Route('/connect/google/check', name: 'connect_google_check', methods: ['GET'])]
    public function check(): never
    {
        // The security firewall handles this callback before the controller.
        throw $this->createNotFoundException();
    }
}
