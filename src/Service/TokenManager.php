<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TokenManager
{
    private $client;
    private $session;
    private $clientId;
    private $clientSecret;
    private $urlToken;
    private $slugAsso;

    public function __construct(HttpClientInterface $client, SessionInterface $session, string $clientId, string $clientSecret, string $urlToken, string $slugAsso)
    {
        $this->client = $client;
        $this->session = $session;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->urlToken = $urlToken;
        $this->slugAsso = $slugAsso;
    }
    public function checkAndUpdateToken()
    {
        try {
            if (!$this->session->has('bearer_token') || !$this->session->has('expiration_token') || (new \DateTime() > $this->session->get('expiration_token')) || (new \DateTime() > $this->session->get('expirationRefreshToken'))) {
                if (new \DateTime() > $this->session->get('expirationRefreshToken')) {
                    $this->session->remove('refresh_token');
                }

                $params = [
                    'grant_type' => $this->session->has('refresh_token') ? 'refresh_token' : 'client_credentials',
                    'client_id' => $this->clientId,
                ];

                if ($this->session->has('refresh_token')) {
                    $params['refresh_token'] = $this->session->get('refresh_token');
                } else {
                    $params['client_secret'] = $this->clientSecret;
                }

                $data = $this->fetchToken($this->urlToken, $params);
                if ($data === null) {
                    // Si fetchToken retourne null, cela signifie que le token n'a pas été récupéré
                    return false;
                }
                $this->updateTokens($data);

                if (new \DateTime() > $this->session->get('expiration_token')) {
                    throw new \Exception('Le token a expiré et la tentative de rafraîchissement a échoué.');
                }
            }
            return true; // Token récupéré avec succès
        } catch (\Exception $e) {
            // Gérer l'exception si nécessaire
            return false; // Échec de la récupération du token
        }
    }
    private function fetchToken($url, $params)
    {
        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query($params),
            ]);

            if ($response->getStatusCode() === 200) {
                return json_decode($response->getContent(), true);
            } else {
                // Gérer les codes de réponse d'erreur
                return null;
            }
        } catch (\Exception $e) {
            // Gérer l'exception si nécessaire
            // Log l'erreur pour un débogage plus facile
            error_log('Erreur lors de la requête API : ' . $e->getMessage());
            return null;
        }
    }

    private function updateTokens($data)
    {
        if (isset($data['access_token'])) {
            $this->session->set('bearer_token', $data['access_token']);

            // Calculer et mettre à jour expiration_token
            $expirationTime = new \DateTime();
            $expirationTime->add(new \DateInterval('PT' . $data['expires_in'] . 'S')); // PT seconds S
            $this->session->set('expiration_token', $expirationTime);

            // Si un refresh_token et son expiration sont retournés, les mettre à jour également
            if (isset($data['refresh_token'])) {
                $this->session->set('refresh_token', $data['refresh_token']);

                // Supposons que l'API retourne également la durée de vie du refresh_token en secondes
                // Si ce n'est pas le cas, vous devrez définir une durée fixe ou gérer cela différemment
                if (isset($data['refresh_expires_in'])) {
                    $refreshExpirationTime = new \DateTime();
                    $refreshExpirationTime->add(new \DateInterval('PT' . $data['refresh_expires_in'] . 'S')); // PT seconds S
                    $this->session->set('expirationRefreshToken', $refreshExpirationTime);
                }
            }
        }
    }
}
