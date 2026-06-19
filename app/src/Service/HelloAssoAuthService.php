<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class HelloAssoAuthService
{
    private RequestStack $requestStack;
    private Client $client;
    private string $apiClientId;
    private string $apiClientSecret;

    public function __construct(RequestStack $requestStack, HttpClientInterface $client, string $apiClientId, string $apiClientSecret)
    {
        $this->requestStack = $requestStack;
        $this->client = $client;
        $this->apiClientId = $apiClientId;
        $this->apiClientSecret = $apiClientSecret;
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    public function getToken(): string
    {
        $session = $this->getSession();
        if (!$session->get('bearer_token') || $session->get('expiration_token') <= new \DateTime() || $session->get('expirationRefreshToken') <= new \DateTime()) {
            if ($session->get('expirationRefreshToken') <= new \DateTime()) {
                $session->remove('refresh_token');
            }

            $params = [
                'grant_type' => $session->get('refresh_token') ? 'refresh_token' : 'client_credentials',
                'client_id' => $this->apiClientId,
            ];

            if ($session->get('refresh_token')) {
                $params['refresh_token'] = $session->get('refresh_token');
            } else {
                $params['client_secret'] = $this->apiClientSecret;
            }

            $response = $this->client->request('POST', '/oauth2/token', ['body' => $params]);
            $dataToken = $response->toArray();

            $session->set('bearer_token', $dataToken['access_token']);
            $session->set('expiration_token', (new \DateTime())->modify('+' . $dataToken['expires_in'] . ' seconds'));
            if (isset($dataToken['refresh_token'])) {
                $session->set('refresh_token', $dataToken['refresh_token']);
                // Assuming the refresh token expires in 14 days
                $session->set('expirationRefreshToken', (new \DateTime())->modify('+14 days'));
            }

            if ($session->get('expiration_token') <= new \DateTime()) {
                throw new \Exception('Le token a expiré et la tentative de rafraîchissement a échoué.');
            }
        }

        return $session->get('bearer_token');
    }
}