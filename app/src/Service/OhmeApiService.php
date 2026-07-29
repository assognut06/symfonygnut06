<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OhmeApiService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }
/**
 * @param array<mixed> $params
 */
    public function getContacts(array $params, string $object, string $method = 'GET'): mixed
    {
        // La base_uri et les en-têtes d'authentification (client-name / client-secret)
        // sont fournis par le client scopé « ohme.client » (voir framework.yaml).
        $response = $this->client->request($method, $object, [
            'query' => $params,
        ]);

        return $response->toArray();
    }
}