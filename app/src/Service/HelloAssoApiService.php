<?php
// Service HelloAssoApiService
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HelloAssoApiService
{
    private $client;
    private $helloAssoAuthService;

    public function __construct(HelloAssoAuthService $helloAssoAuthService, HttpClientInterface $client)
    {
        $this->client = $client;
        $this->helloAssoAuthService = $helloAssoAuthService;
    }

    public function makeApiCall(string $url, array $headers = [], string $method = 'GET')
    {
        $bearerToken = $this->helloAssoAuthService->getToken();
        $authorization = "Bearer " . $bearerToken;
        $headers['authorization'] = $authorization;
        try {
            $response = $this->client->request($method, $url, [
                'headers' => $headers,
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            // Gérer l'exception ou la logger
            // return $e;
            return false;
        }
    }
    // Autres méthodes pour interagir avec l'API HelloAsso...
}