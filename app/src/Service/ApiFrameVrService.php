<?php
// src/Service/ApiFrameVrService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiFrameVrService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function getSomeData(string $id, string $object, string $method = 'GET'): array
    {
        $urlBaseApi = "https://api.framevr.io/automate/v1/";
        $url =  $urlBaseApi . $object . '/' . $id;
        $headers = [
            'accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];
        $response = $this->client->request(
            $method,
            $url,
            [
                'headers' => $headers,
            ]
        );

        return $response->toArray();
    }
}