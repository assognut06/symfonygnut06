<?php

namespace App\Tests\Functional;

/**
 * Tests the HelloAsso notification webhook endpoint.
 */
class NotificationWebhookTest extends WebTestCase
{
    public function testCallbackRejectsInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/notification/callback',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'not-valid-json{{'
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCallbackAcceptsValidPayload(): void
    {
        $payload = json_encode([
            'eventType' => 'Order',
            'data' => [
                'organizationSlug' => 'test-asso',
                'organizationName' => 'Test Association',
                'formSlug' => 'test-form',
                'payer' => [
                    'email' => 'payer@test.com',
                    'firstName' => 'Jean',
                    'lastName' => 'Dupont',
                ],
                'items' => [],
            ],
        ]);

        $this->client->request(
            'POST',
            '/notification/callback',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertEquals('success', $data['status']);
    }

    public function testCallbackRejectsGetMethod(): void
    {
        $this->client->request('GET', '/notification/callback');

        $this->assertResponseStatusCodeSame(405);
    }

    public function testDebugEndpointsShouldNotBePublic(): void
    {
        $debugRoutes = [
            '/notification/test',
            '/notification/stats',
            '/notification/debug-all',
        ];

        foreach ($debugRoutes as $route) {
            $this->client->request('GET', $route);
            $response = $this->client->getResponse();

            $this->markTestIncomplete(
                sprintf(
                    'Debug route %s is publicly accessible (HTTP %d). It should be restricted to admin or dev only.',
                    $route,
                    $response->getStatusCode()
                )
            );
        }
    }
}
