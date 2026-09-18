<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Verifies the HTTPS redirection through the local Apache server.
 */
final class HttpsRedirectTest extends TestCase
{
    /** @dataProvider localRequestProvider */
    public function testLocalApacheRedirectsHttpToHttps(
        string $method,
        string $requestUri,
        string $expectedLocation,
    ): void {
        $response = HttpClient::create()->request($method, 'http://127.0.0.1'.$requestUri, [
            'max_redirects' => 0,
            'timeout' => 5,
            'no_proxy' => '*',
        ]);

        self::assertSame(308, $response->getStatusCode());
        self::assertSame([$expectedLocation], $response->getHeaders(false)['location'] ?? []);
    }

    public static function localRequestProvider(): iterable
    {
        yield 'home' => ['GET', '/', 'https://127.0.0.1/'];
        yield 'route and query string' => [
            'GET',
            '/login?next=%2Fprofil',
            'https://127.0.0.1/login?next=%2Fprofil',
        ];
        yield 'static file' => ['GET', '/robots.txt', 'https://127.0.0.1/robots.txt'];
        yield 'public TIH search' => [
            'GET',
            '/tih/tih_search',
            'https://127.0.0.1/tih/tih_search',
        ];
        yield 'intranet TIH search' => [
            'GET',
            '/intranet/tih/tih_search',
            'https://127.0.0.1/intranet/tih/tih_search',
        ];
        yield 'POST keeps its method' => ['POST', '/login', 'https://127.0.0.1/login'];
    }
}
