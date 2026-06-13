<?php

namespace App\Service;

use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiFrameVrService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $id
     * @param string $object
     * @param string $method
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getSomeData(string $id, string $object, string $method = 'GET'): array
    {
        $urlBaseApi = 'https://api.framevr.io/automate/v1/';
        $url = $urlBaseApi.$object.'/'.$id;
        $headers = [
            'accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->apiKey,
        ];
        try {
            $response = $this->client->request(
                $method,
                $url,
                [
                    'headers' => $headers,
                ]
            );
        } catch (HttpTransportException $e) {
            throw new \RuntimeException('Erreur lors de la communication avec l\'API FrameVR: '.$e->getMessage());
        }

        return $response->toArray();
    }
}
