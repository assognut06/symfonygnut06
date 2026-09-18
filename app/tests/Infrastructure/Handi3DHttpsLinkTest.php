<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

final class Handi3DHttpsLinkTest extends TestCase
{
    public function testHttpRedirectsToPageContainingHttpsTihSearchLink(): void
    {
        $client = HttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        $httpResponse = $client->request('GET', 'http://127.0.0.1/Handi-3D', [
            'max_redirects' => 0,
            'timeout' => 5,
            'no_proxy' => '*',
        ]);

        self::assertSame(308, $httpResponse->getStatusCode());
        self::assertSame(
            ['https://127.0.0.1/Handi-3D'],
            $httpResponse->getHeaders(false)['location'] ?? [],
        );

        $httpsResponse = $client->request('GET', 'https://127.0.0.1/Handi-3D', [
            'timeout' => 5,
            'no_proxy' => '*',
        ]);

        self::assertSame(200, $httpsResponse->getStatusCode());
        self::assertStringContainsString(
            'href="https://127.0.0.1/tih/tih_search"',
            $httpsResponse->getContent(),
        );
    }
}
