<?php
// Service HelloAssoApiService
namespace App\Service;

use GuzzleHttp\Client;

class HelloAssoApiService
{
    private $client;
    private $helloAssoAuthService;

    public function __construct(HelloAssoAuthService $helloAssoAuthService)
    {
        $this->client = new Client();
        $this->helloAssoAuthService = $helloAssoAuthService;
    }

    public function makeApiCall(string $url, array $headers = [], string $method = 'GET')
    {
        $bearerToken = $this->helloAssoAuthService->getToken();
        $authorization = "Bearer " . $bearerToken;
        $headers['authorization'] = $authorization;
        try {
            $response = $this->client->request($method, $url, [
                'accept' => 'application/json',
                'headers' => $headers,  
            ]);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            // Gérer l'exception ou la logger
            return null;
        }
    }
    // Autres méthodes pour interagir avec l'API HelloAsso...
}