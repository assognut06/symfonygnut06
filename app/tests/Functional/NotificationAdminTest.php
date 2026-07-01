<?php

namespace App\Tests\Functional;

use App\Entity\HelloAssoFormNotification;

/**
 * Tests admin-only notification monitoring endpoints.
 */
class NotificationAdminTest extends WebTestCase
{
    /**
     * @dataProvider notificationAdminUrlProvider
     */
    public function testNotificationEndpointRedirectsAnonymous(string $url): void
    {
        $this->client->request('GET', $url);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isRedirection(),
            sprintf('Notification URL "%s" should redirect anonymous users, got %d.', $url, $response->getStatusCode())
        );
    }

    /**
     * @dataProvider notificationAdminUrlProvider
     */
    public function testNotificationEndpointDeniesRegularUser(string $url): void
    {
        $this->loginAsNewUser();

        $this->client->request('GET', $url);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->getStatusCode() === 403 || $response->isRedirection(),
            sprintf('Notification URL "%s" should deny regular user, got %d.', $url, $response->getStatusCode())
        );
    }

    public function testTestEndpointReturnsOkForAdmin(): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/notification/test');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('ok', $data['status']);
        $this->assertTrue($data['entities']['HelloAssoFormNotification']);
        $this->assertTrue($data['entities']['Payers']);
        $this->assertTrue($data['entities']['AssoRecommander']);
    }

    public function testStatsReturnsCountsForAdmin(): void
    {
        $this->loginAsAdmin();

        $notification = HelloAssoFormNotification::fromHelloAssoPayload([
            'eventType' => 'Order',
            'data' => [
                'organizationSlug' => 'stats-org',
                'organizationName' => 'Stats Org',
                'formSlug' => 'stats-form',
            ],
        ]);
        $this->em->persist($notification);
        $this->em->flush();

        $this->client->request('GET', '/notification/stats');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('success', $data['status']);
        $this->assertGreaterThanOrEqual(1, $data['stats']['notifications']);
        $this->assertArrayHasKey('payers', $data['stats']);
        $this->assertArrayHasKey('associations', $data['stats']);
    }

    public function testDebugAllReturns404WhenFixtureMissing(): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/notification/debug-all');

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame(
            'Notification not found',
            json_decode($this->client->getResponse()->getContent(), true)['error']
        );
    }

    public function testDebugAllReturnsStructureWhenFixtureExists(): void
    {
        $this->loginAsAdmin();

        $notification = HelloAssoFormNotification::fromHelloAssoPayload([
            'eventType' => 'FormPublished',
            'data' => [
                'organizationSlug' => 'gnut-test-association',
                'organizationName' => 'GNUT Test Association',
                'formSlug' => 'debug-form',
                'title' => 'Formulaire debug',
                'tiers' => [
                    [
                        'id' => 42,
                        'label' => 'Don libre',
                        'price' => 10,
                        'tierType' => 'Donation',
                        'isFavorite' => true,
                        'customFields' => [
                            [
                                'id' => 7,
                                'label' => 'Message',
                                'type' => 'Text',
                                'isRequired' => false,
                                'values' => ['hello'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $this->em->persist($notification);
        $this->em->flush();

        $this->client->request('GET', '/notification/debug-all');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('Formulaire debug', $data['notification']['title']);
        $this->assertSame('GNUT Test Association', $data['notification']['organization']);
        $this->assertSame('FormPublished', $data['notification']['event_type']);
        $this->assertCount(1, $data['tiers']);
        $this->assertSame('Don libre', $data['tiers'][0]['label']);
        $this->assertCount(1, $data['tiers'][0]['custom_fields']);
        $this->assertSame('Message', $data['custom_fields'][0]['label']);
    }

    /**
     * @dataProvider notificationAdminUrlProvider
     */
    public function testNotificationEndpointAccessibleByAdmin(string $url): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', $url);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->getStatusCode() === 404,
            sprintf('Notification URL "%s" should be accessible by admin, got %d.', $url, $response->getStatusCode())
        );
    }

    public static function notificationAdminUrlProvider(): iterable
    {
        yield 'Test endpoint' => ['/notification/test'];
        yield 'Stats endpoint' => ['/notification/stats'];
        yield 'Debug all endpoint' => ['/notification/debug-all'];
    }
}
