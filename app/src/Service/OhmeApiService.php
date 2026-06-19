<?php

namespace App\Service;

use GuzzleHttp\Client;

class OhmeApiService
{
    private Client $client;
    private string $slugAsso;
    private string $secretOhme;

    public function __construct(string $slugAsso, string $secretOhme)
    {
        $this->client = new Client();
        $this->slugAsso = $slugAsso;
        $this->secretOhme = $secretOhme;
    }
/**
 * @param array<mixed> $params
 */
    public function getContacts(array $params, string $object, string $method = 'GET'): mixed
    {
        $urlBaseApi = 'https://api-ohme.oneheart.fr/api/v1/';
        $url = $urlBaseApi.$object.'?'.http_build_query($params);

        $headers = [
            'client-name' => $this->slugAsso,
            'client-secret' => $this->secretOhme,
        ];

        $response = $this->client->request($method, $url, [
            'Accept' => 'application/json',
            'headers' => $headers,
        ]);

        return json_decode($response->getBody(), true);
    }
}
