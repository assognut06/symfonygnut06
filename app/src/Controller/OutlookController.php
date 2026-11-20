<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\OAuth\OAuthFlowManager;
use App\Security\OAuth\OAuthFlowPurpose;
use App\Security\OAuth\OAuthProvider;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class OutlookController extends AbstractController
{
    #[Route('/connect/outlook', name: 'connect_outlook_start', methods: ['GET'])]
    public function connect(Request $request, ClientRegistry $clientRegistry, OAuthFlowManager $flowManager): RedirectResponse
    {
        $flow = $flowManager->start($request, OAuthProvider::Microsoft);
        if ($this->getUser() instanceof User && OAuthFlowPurpose::Login === $flow['purpose']) {
            $flowManager->clearFlow($request, OAuthProvider::Microsoft);
            $this->addFlash('error', 'Démarrez une liaison depuis votre profil pour ajouter Microsoft.');

            return $this->redirectToRoute('app_profil');
        }

        $options = ['nonce' => $flow['nonce']];
        if (OAuthFlowPurpose::Reauthenticate === $flow['purpose']) {
            $options['prompt'] = 'login';
        }

        return $clientRegistry->getClient('azure')->redirect([], $options);
    }

    #[Route('/connect/outlook/check', name: 'connect_outlook_check', methods: ['GET'])]
    public function check(): never
    {
        throw $this->createNotFoundException();
    }
}
