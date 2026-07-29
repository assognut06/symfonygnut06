<?php

namespace App\Tests\Stub;

use App\Service\HelloAssoApiService;

/**
 * Stub that prevents real HTTP calls to HelloAsso API during tests.
 */
class HelloAssoApiServiceStub extends HelloAssoApiService
{
    public function __construct()
    {
        // Skip parent constructor to avoid requiring HelloAssoAuthService
    }

    public function makeApiCall(string $url, array $headers = [], string $method = 'GET') :mixed
    {
        if (str_contains($url, '/organizations/') && !str_contains($url, '/items') && !str_contains($url, '/payments') && !str_contains($url, '/forms/')) {
            return [
                'name' => 'Test Association',
                'logo' => 'https://example.com/logo.png',
                'description' => 'Association de test.',
                'type' => 'Association1901Rig',
                'category' => 'Test',
                'zipCode' => '06000',
                'city' => 'Nice',
                'rnaNumber' => 'W061000000',
                'url' => 'https://www.helloasso.com/associations/test',
            ];
        }

        return [
            'data' => [],
            'pagination' => [
                'pageIndex' => 1,
                'pageSize' => 15,
                'totalCount' => 0,
                'totalPages' => 0,
            ],
        ];
    }
}
