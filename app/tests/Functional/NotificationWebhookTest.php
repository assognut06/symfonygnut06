<?php

namespace App\Tests\Functional;

use App\Entity\AssoRecommander;
use App\Entity\HelloAssoFormNotification;
use App\Entity\Payers;

/**
 * Tests the HelloAsso notification webhook endpoint.
 */
class NotificationWebhookTest extends WebTestCase
{
    private const ALLOWED_WEBHOOK_IP = '51.138.206.200';
    private const TIMESTAMP_HEADER = 'HTTP_X_HELLOASSO_TIMESTAMP';
    private const SIGNATURE_HEADER = 'HTTP_X_HELLOASSO_SIGNATURE';
    private const EVENT_ID_HEADER = 'HTTP_X_HELLOASSO_EVENT_ID';
    private const WEBHOOK_SECRET = 'test-webhook-secret';

    public function testCallbackRejectsInvalidJson(): void
    {
        $this->postWebhook('not-valid-json{{');

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(
            'Invalid JSON',
            json_decode($this->client->getResponse()->getContent(), true)['error']
        );
    }

    public function testCallbackAcceptsValidPayload(): void
    {
        $this->postWebhook($this->buildOrderPayload());

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame('success', $data['status']);
        $this->assertSame('Notification processed successfully', $data['message']);
        $this->assertSame('Order', $data['eventType']);
    }

    public function testCallbackRejectsGetMethod(): void
    {
        $this->client->request('GET', '/notification/callback');

        $this->assertResponseStatusCodeSame(405);
    }

    public function testCallbackRejectsUnauthorizedIp(): void
    {
        $this->postWebhook($this->buildOrderPayload(), ['REMOTE_ADDR' => '203.0.113.50']);

        $this->assertResponseStatusCodeSame(401);
        $this->assertSame(
            'Unauthorized',
            json_decode($this->client->getResponse()->getContent(), true)['error']
        );
    }

    public function testCallbackRejectsInvalidTimestamp(): void
    {
        $this->postWebhook($this->buildOrderPayload(), [
            self::TIMESTAMP_HEADER => (string) (time() - 86400),
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCallbackAcceptsValidTimestamp(): void
    {
        $this->postWebhook($this->buildOrderPayload(), [
            self::TIMESTAMP_HEADER => (string) time(),
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testCallbackRejectsInvalidSignature(): void
    {
        $content = $this->buildOrderPayload();

        $this->postWebhook($content, [
            self::SIGNATURE_HEADER => 'invalid-signature',
        ], sign: false);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCallbackAcceptsSignatureWithTimestampPrefix(): void
    {
        $content = $this->buildOrderPayload();
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $content, $this->getWebhookSecret());

        $this->postWebhook($content, [
            self::TIMESTAMP_HEADER => $timestamp,
            self::SIGNATURE_HEADER => 'sha256=' . $signature,
        ], sign: false);

        $this->assertResponseIsSuccessful();
    }

    public function testCallbackRejectsPayloadTooLarge(): void
    {
        $this->postWebhook(str_repeat('a', 1_048_577));

        $this->assertResponseStatusCodeSame(413);
        $this->assertSame(
            'Payload too large',
            json_decode($this->client->getResponse()->getContent(), true)['error']
        );
    }

    public function testCallbackReturnsDuplicateForSameEventId(): void
    {
        $payload = $this->buildOrderPayload('duplicate-event-id');

        $this->postWebhook($payload);
        $this->assertResponseIsSuccessful();

        $this->postWebhook($payload);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
        $this->assertSame('duplicate', $data['status']);
        $this->assertSame('Notification already processed', $data['message']);
    }

    public function testCallbackUsesHelloAssoEventIdHeaderForIdempotency(): void
    {
        $eventId = 'header-event-' . uniqid();
        $firstPayload = $this->buildOrderPayload('payload-event-a');
        $secondPayload = $this->buildOrderPayload('payload-event-b');

        $this->postWebhook($firstPayload, [self::EVENT_ID_HEADER => $eventId]);
        $this->assertResponseIsSuccessful();

        $this->postWebhook($secondPayload, [self::EVENT_ID_HEADER => $eventId]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('duplicate', $data['status']);
    }

    public function testCallbackPersistsHelloAssoNotificationOnOrder(): void
    {
        $repository = $this->em->getRepository(HelloAssoFormNotification::class);
        $slug = 'persist-order-' . uniqid();

        $this->postWebhook($this->buildOrderPayload(null, [
            'organizationSlug' => $slug,
            'organizationName' => 'Persist Order Org',
        ]));

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $notification = $repository->findOneBy(['organizationSlug' => $slug]);

        $this->assertNotNull($notification);
        $this->assertSame('Order', $notification->getEventType());
        $this->assertSame('Persist Order Org', $notification->getOrganizationName());
        $this->assertSame('test-form', $notification->getFormSlug());
    }

    public function testCallbackProcessesUnknownEventType(): void
    {
        $slug = 'unknown-event-' . uniqid();

        $this->postWebhook(json_encode([
            'eventId' => 'unknown-' . uniqid('', true),
            'eventType' => 'UnknownHelloAssoEvent',
            'data' => [
                'organizationSlug' => $slug,
                'organizationName' => 'Unknown Event Org',
                'formSlug' => 'unknown-form',
            ],
        ]));

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $notification = $this->em->getRepository(HelloAssoFormNotification::class)
            ->findOneBy(['organizationSlug' => $slug]);

        $this->assertNotNull($notification);
        $this->assertSame('UnknownHelloAssoEvent', $notification->getEventType());
    }

    public function testCallbackCreatesPayerOnOrderEvent(): void
    {
        $email = 'new-payer-' . uniqid() . '@test.com';
        $repository = $this->em->getRepository(Payers::class);

        $this->postWebhook($this->buildOrderPayload(null, [
            'payer' => [
                'email' => $email,
                'firstName' => 'Alice',
                'lastName' => 'Martin',
                'city' => 'Nice',
            ],
        ]));

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $payer = $repository->findOneBy(['email' => $email]);

        $this->assertNotNull($payer);
        $this->assertSame('Alice', $payer->getFirstName());
        $this->assertSame('Martin', $payer->getLastName());
        $this->assertSame('Nice', $payer->getCity());
    }

    public function testCallbackUpdatesExistingPayerOnOrderEvent(): void
    {
        $email = 'existing-payer-' . uniqid() . '@test.com';
        $payer = new Payers();
        $payer->setEmail($email);
        $payer->setFirstName('Old');
        $payer->setLastName('Name');
        $payer->setCreatedAt(new \DateTimeImmutable());
        $payer->setUpdatedAt(new \DateTime());
        $this->em->persist($payer);
        $this->em->flush();

        $this->postWebhook($this->buildOrderPayload(null, [
            'payer' => [
                'email' => $email,
                'firstName' => 'Updated',
                'lastName' => 'Payer',
            ],
        ]));

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $updatedPayer = $this->em->getRepository(Payers::class)->findOneBy(['email' => $email]);

        $this->assertNotNull($updatedPayer);
        $this->assertSame('Updated', $updatedPayer->getFirstName());
        $this->assertSame('Payer', $updatedPayer->getLastName());
    }

    public function testCallbackSkipsPayerCreationWhenEmailMissing(): void
    {
        $beforeCount = count($this->em->getRepository(Payers::class)->findAll());

        $this->postWebhook($this->buildOrderPayload(null, [
            'payer' => [
                'firstName' => 'Sans',
                'lastName' => 'Email',
            ],
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertCount($beforeCount, $this->em->getRepository(Payers::class)->findAll());
    }

    /**
     * @dataProvider formEventTypeProvider
     */
    public function testCallbackProcessesFormEventAndCreatesAsso(string $eventType): void
    {
        $slug = 'new-asso-' . uniqid() . '-' . $eventType;
        $repository = $this->em->getRepository(AssoRecommander::class);

        $this->postWebhook(json_encode([
            'eventId' => 'form-' . uniqid('', true),
            'eventType' => $eventType,
            'data' => [
                'organizationSlug' => $slug,
                'organizationName' => 'Association Test',
                'formSlug' => 'adhesion-form',
                'title' => 'Adhesion 2026',
                'placeCity' => 'Nice',
                'placeZipCode' => '06000',
            ],
        ]));

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $asso = $repository->findOneBy(['organizationSlug' => $slug]);

        $this->assertNotNull($asso);
        $this->assertSame('Association Test', $asso->getName());
        $this->assertSame('Nice', $asso->getCity());
    }

    /**
     * @return iterable<array<int,string>>
     */
    public static function formEventTypeProvider(): iterable
    {
        yield 'Form' => ['Form'];
        yield 'FormPublished' => ['FormPublished'];
        yield 'FormUpdated' => ['FormUpdated'];
    }

    public function testCallbackUpdatesExistingAssoOnFormEvent(): void
    {
        $slug = 'existing-asso-' . uniqid();
        $asso = new AssoRecommander();
        $asso->setOrganizationSlug($slug);
        $asso->setName('Old Association Name');
        $this->em->persist($asso);
        $this->em->flush();

        $this->postWebhook(json_encode([
            'eventId' => 'form-update-' . uniqid('', true),
            'eventType' => 'FormUpdated',
            'data' => [
                'organizationSlug' => $slug,
                'organizationName' => 'Updated Association Name',
                'formSlug' => 'updated-form',
                'placeCity' => 'Cannes',
            ],
        ]));

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $updatedAsso = $this->em->getRepository(AssoRecommander::class)->findOneBy(['organizationSlug' => $slug]);

        $this->assertNotNull($updatedAsso);
        $this->assertSame('Updated Association Name', $updatedAsso->getName());
        $this->assertSame('Cannes', $updatedAsso->getCity());
    }

    public function testCallbackDoesNotCreateAssoWhenOrganizationSlugMissing(): void
    {
        $beforeCount = count($this->em->getRepository(AssoRecommander::class)->findAll());

        $this->postWebhook(json_encode([
            'eventId' => 'form-no-slug-' . uniqid('', true),
            'eventType' => 'FormPublished',
            'data' => [
                'organizationName' => 'Association sans slug',
                'formSlug' => 'orphan-form',
            ],
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertCount($beforeCount, $this->em->getRepository(AssoRecommander::class)->findAll());
    }

    public function testCallbackPersistsFormNotificationWithTiers(): void
    {
        $slug = 'tiers-org-' . uniqid();

        $this->postWebhook(json_encode([
            'eventId' => 'tiers-' . uniqid('', true),
            'eventType' => 'FormPublished',
            'data' => [
                'organizationSlug' => $slug,
                'organizationName' => 'Tiers Org',
                'formSlug' => 'don-form',
                'tiers' => [
                    [
                        'id' => 101,
                        'label' => 'Don libre',
                        'price' => 15,
                        'tierType' => 'Donation',
                        'isFavorite' => true,
                        'customFields' => [
                            [
                                'id' => 501,
                                'label' => 'Message',
                                'type' => 'Text',
                                'isRequired' => false,
                                'values' => ['merci'],
                            ],
                        ],
                    ],
                ],
            ],
        ]));

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $notification = $this->em->getRepository(HelloAssoFormNotification::class)
            ->findOneBy(['organizationSlug' => $slug]);

        $this->assertNotNull($notification);
        $this->assertSame(1, $notification->getTierCount());
        $this->assertSame('Don libre', $notification->getTiers()->first()->getLabel());
    }

    public function testCallbackProcessesPaymentEventWithPayer(): void
    {
        $repository = $this->em->getRepository(HelloAssoFormNotification::class);
        $slug = 'payment-org-' . uniqid();

        $this->postWebhook(json_encode([
            'eventId' => 'payment-' . uniqid('', true),
            'eventType' => 'Payment',
            'data' => [
                'organizationSlug' => $slug,
                'organizationName' => 'Payment Org',
                'formSlug' => 'don-form',
                'state' => 'Authorized',
                'amount' => 2500,
                'payer' => [
                    'email' => 'payment-payer@test.com',
                    'firstName' => 'Paul',
                    'lastName' => 'Durand',
                ],
            ],
        ]));

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $notification = $repository->findOneBy(['organizationSlug' => $slug]);

        $this->assertNotNull($notification);
        $this->assertSame('Payment', $notification->getEventType());
    }

    public function testCallbackProcessesOrderItems(): void
    {
        $this->postWebhook(json_encode([
            'eventId' => 'order-items-' . uniqid('', true),
            'eventType' => 'Order',
            'data' => [
                'organizationSlug' => 'items-org-' . uniqid(),
                'organizationName' => 'Items Org',
                'formSlug' => 'shop-form',
                'items' => [
                    [
                        'name' => 'Casque VR',
                        'amount' => 5000,
                        'type' => 'Product',
                        'state' => 'Processed',
                    ],
                ],
            ],
        ]));

        $this->assertResponseIsSuccessful();
    }

    
    /**
     * @param array<mixed> $dataOverrides
     */
    private function buildOrderPayload(?string $eventId = null, array $dataOverrides = []): string
    {
        return json_encode([
            'eventId' => $eventId ?? 'test-' . uniqid('', true),
            'eventType' => 'Order',
            'data' => array_merge([
                'organizationSlug' => 'test-asso',
                'organizationName' => 'Test Association',
                'formSlug' => 'test-form',
                'payer' => [
                    'email' => 'payer@test.com',
                    'firstName' => 'Jean',
                    'lastName' => 'Dupont',
                ],
                'items' => [],
            ], $dataOverrides),
        ]);
    }

    /**
     * @param array<mixed> $extraServer
     */
    private function postWebhook(string $content, array $extraServer = [], bool $sign = true): void
    {
        $server = array_merge([
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => self::ALLOWED_WEBHOOK_IP,
        ], $extraServer);

        if ($sign && !array_key_exists(self::SIGNATURE_HEADER, $server)) {
            $secret = $this->getWebhookSecret();
            if ($secret !== '') {
                $server[self::SIGNATURE_HEADER] = hash_hmac('sha256', $content, $secret);
            }
        }

        $this->client->request(
            'POST',
            '/notification/callback',
            [],
            [],
            $server,
            $content
        );
    }

    private function getWebhookSecret(): string
    {
        return self::WEBHOOK_SECRET;
    }
}
