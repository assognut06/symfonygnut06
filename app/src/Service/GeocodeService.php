<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeocodeService
{
    private const CACHE_TTL = 2592000; // 30 days

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly string $googleMapsApiKey
    ) {
    }

    /**
     * Get coordinates for a city in France
     * Uses cache to avoid repeated API calls
     */
    public function getCityCoordinates(string $city): ?array
    {
        $cacheKey = 'geocode_' . md5(strtolower($city) . '_france');

        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($city) {
                $item->expiresAfter(self::CACHE_TTL);

                $address = urlencode($city . ', France');
                $url = sprintf(
                    'https://maps.googleapis.com/maps/api/geocode/json?address=%s&key=%s',
                    $address,
                    $this->googleMapsApiKey
                );

                $response = $this->httpClient->request('GET', $url);
                $data = $response->toArray();

                if ($data['status'] === 'OK' && !empty($data['results'][0])) {
                    $location = $data['results'][0]['geometry']['location'];
                    
                    $this->logger->info('Geocoded city', [
                        'city' => $city,
                        'lat' => $location['lat'],
                        'lng' => $location['lng']
                    ]);

                    return [
                        'lat' => $location['lat'],
                        'lng' => $location['lng']
                    ];
                }

                $this->logger->warning('Geocoding failed', [
                    'city' => $city,
                    'status' => $data['status']
                ]);

                return null;
            });
        } catch (\Exception $e) {
            $this->logger->error('Geocoding error', [
                'city' => $city,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get coordinates for multiple cities at once
     * Returns an associative array: ['CityName' => ['lat' => x, 'lng' => y]]
     */
    public function getCitiesCoordinates(array $cities): array
    {
        $coordinates = [];
        
        foreach ($cities as $city) {
            if ($coords = $this->getCityCoordinates($city)) {
                $coordinates[$city] = $coords;
            }
        }

        return $coordinates;
    }
}
