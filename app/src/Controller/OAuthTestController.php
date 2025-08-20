<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/oauth-test')]
class OAuthTestController extends AbstractController
{
    #[Route('/', name: 'oauth_test')]
    public function index(ClientRegistry $clientRegistry): Response
    {
        try {
            // Test du client Azure
            $azureClient = $clientRegistry->getClient('azure');
            $azureStatus = '✅ Client Azure OK';
            $azureError = null;
        } catch (\Exception $e) {
            $azureStatus = '❌ Erreur client Azure';
            $azureError = $e->getMessage();
        }

        try {
            // Test du client Google
            $googleClient = $clientRegistry->getClient('google');
            $googleStatus = '✅ Client Google OK';
            $googleError = null;
        } catch (\Exception $e) {
            $googleStatus = '❌ Erreur client Google';
            $googleError = $e->getMessage();
        }

        return $this->render('oauth_test/index.html.twig', [
            'azure_status' => $azureStatus,
            'azure_error' => $azureError,
            'google_status' => $googleStatus,
            'google_error' => $googleError,
            'azure_client_id' => $_ENV['AZURE_CLIENT_ID'] ?? 'NON DÉFINI',
            'google_client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? 'NON DÉFINI',
        ]);
    }

    #[Route('/test-azure', name: 'oauth_test_azure')]
    public function testAzure(ClientRegistry $clientRegistry): Response
    {
        try {
            $client = $clientRegistry->getClient('azure');
            return $client->redirect([
                'https://graph.microsoft.com/User.Read',
                'https://graph.microsoft.com/profile',
                'https://graph.microsoft.com/email',
                'openid',
                'profile',
                'email'
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur Azure: ' . $e->getMessage());
            return $this->redirectToRoute('oauth_test');
        }
    }

    #[Route('/debug-azure-callback', name: 'debug_azure_callback')]
    public function debugAzureCallback(ClientRegistry $clientRegistry): Response
    {
        try {
            $client = $clientRegistry->getClient('azure');

            // Utiliser la méthode correcte pour récupérer le token
            $accessToken = $client->fetchAccessToken();
            $azureUser = $client->fetchUserFromToken($accessToken);
            $userData = $azureUser->toArray();

            return $this->json([
                'success' => true,
                'user_data' => $userData,
                'available_fields' => array_keys($userData),
                'id' => $azureUser->getId(),
                'email_attempts' => [
                    'mail' => $userData['mail'] ?? 'NON TROUVÉ',
                    'userPrincipalName' => $userData['userPrincipalName'] ?? 'NON TROUVÉ',
                    'email' => $userData['email'] ?? 'NON TROUVÉ',
                    'preferred_username' => $userData['preferred_username'] ?? 'NON TROUVÉ'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    #[Route('/test-google', name: 'oauth_test_google')]
    public function testGoogle(ClientRegistry $clientRegistry): Response
    {
        try {
            $client = $clientRegistry->getClient('google');
            return $client->redirect();
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur Google: ' . $e->getMessage());
            return $this->redirectToRoute('oauth_test');
        }
    }
}
