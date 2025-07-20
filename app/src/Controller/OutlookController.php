<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class OutlookController extends AbstractController
{
    /**
     * Lien pour se connecter avec Outlook/Microsoft.
     * Cette action redirige l'utilisateur vers la page d'authentification de Microsoft.
     */
    #[Route('/connect/outlook', name: 'connect_outlook_start')]
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        // 'azure' est le nom de notre client configuré dans knpu_oauth2_client.yaml
        return $clientRegistry
            ->getClient('azure')
            ->redirect();
    }

    /**
     * Après autorisation de Microsoft, l'utilisateur est redirigé ici.
     * La logique d'authentification est gérée par notre OutlookAuthenticator,
     * cette méthode peut donc rester vide.
     */
    #[Route('/connect/outlook/check', name: 'connect_outlook_check')]
    public function connectCheckAction(): RedirectResponse
    {
        // Cette action ne sera jamais exécutée car le pare-feu (firewall) de Symfony
        // intercepte la requête avant qu'elle n'arrive ici.
        return $this->redirectToRoute('app_profil');
    }
}
