<?php

namespace App\Tests\Functional;

/**
 * Verifies that security headers are properly set on responses.
 */
class SecurityHeadersTest extends WebTestCase
{
    public function testXFrameOptionsHeader(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseHeaderSame('X-Frame-Options', 'SAMEORIGIN');
    }

    public function testXContentTypeOptionsHeader(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
    }

    public function testXXssProtectionHeader(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseHeaderSame('X-XSS-Protection', '1; mode=block');
    }

    public function testReferrerPolicyHeader(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseHeaderSame('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /**
     * @dataProvider publicRouteProvider
     */
    public function testSecurityHeadersPresentOnRoute(string $route): void
    {
        $this->client->request('GET', $route);

        $response = $this->client->getResponse();
        $this->assertEquals(
            'nosniff',
            $response->headers->get('X-Content-Type-Options'),
            sprintf('X-Content-Type-Options missing on %s', $route)
        );
        $this->assertEquals(
            'SAMEORIGIN',
            $response->headers->get('X-Frame-Options'),
            sprintf('X-Frame-Options missing on %s', $route)
        );
    }

    public static function publicRouteProvider(): iterable
    {
        yield 'Home' => ['/'];
        yield 'Login' => ['/login'];
        yield 'Register' => ['/register'];
        yield 'Contact' => ['/contact'];
    }

    public function testNoPoweredByHeader(): void
    {
        $this->client->request('GET', '/');

        $response = $this->client->getResponse();
        $this->assertNull(
            $response->headers->get('X-Powered-By'),
            'X-Powered-By header should not be exposed'
        );
    }
}
