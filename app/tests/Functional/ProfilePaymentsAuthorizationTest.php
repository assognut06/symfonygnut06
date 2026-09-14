<?php

namespace App\Tests\Functional;

use App\Service\HelloAssoApiService;

class ProfilePaymentsAuthorizationTest extends WebTestCase
{
    public function testPaymentsPageOnlyDisplaysPaymentsOwnedByConnectedUser(): void
    {
        $user = $this->createUser('member@example.test');
        $this->loginAs($user);

        $helloAssoApi = $this->createMock(HelloAssoApiService::class);
        $helloAssoApi->expects($this->once())
            ->method('makeApiCall')
            ->with($this->stringContains('userSearchKey=member%40example.test'))
            ->willReturn([
                'data' => [
                    $this->payment('owned-payment', ' MEMBER@EXAMPLE.TEST '),
                    $this->payment('victim-payment', 'victim@example.test'),
                    $this->payment('payment-without-owner'),
                    null,
                    'invalid-payment',
                    ['id' => 'invalid-payer', 'payer' => 'member@example.test'],
                    ['id' => 'array-email', 'payer' => ['email' => ['member@example.test']]],
                    ['id' => 'object-email', 'payer' => ['email' => new \stdClass()]],
                    ['id' => 'numeric-email', 'payer' => ['email' => 123]],
                    ['id' => 'boolean-email', 'payer' => ['email' => true]],
                    ['id' => 'null-email', 'payer' => ['email' => null]],
                    ['id' => 'blank-email', 'payer' => ['email' => '   ']],
                ],
                'pagination' => ['totalCount' => 12],
            ]);
        static::getContainer()->set(HelloAssoApiService::class, $helloAssoApi);

        $this->client->request('GET', '/profil/payments/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#profile-content', 'owned-payment');
        $this->assertSelectorTextNotContains('#profile-content', 'victim-payment');
        $this->assertSelectorTextNotContains('#profile-content', 'payment-without-owner');
        $this->assertSelectorCount(1, '#profile-content [role="listitem"]');
    }

    /**
     * @dataProvider malformedPaymentDataProvider
     * @param array<string, mixed> $response
     */
    public function testMalformedPaymentDataDisplaysAnEmptyList(array $response): void
    {
        $this->loginAs($this->createUser('member@example.test'));

        $helloAssoApi = $this->createMock(HelloAssoApiService::class);
        $helloAssoApi->expects($this->once())
            ->method('makeApiCall')
            ->willReturn($response);
        static::getContainer()->set(HelloAssoApiService::class, $helloAssoApi);

        $this->client->request('GET', '/profil/payments/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#profile-content', 'Aucun paiement à afficher pour le moment.');
        $this->assertSelectorCount(0, '#profile-content [role="listitem"]');
    }

    public static function malformedPaymentDataProvider(): iterable
    {
        yield 'missing data' => [[]];
        yield 'null data' => [['data' => null]];
        yield 'string data' => [['data' => 'invalid']];
        yield 'integer data' => [['data' => 123]];
        yield 'boolean data' => [['data' => true]];
        yield 'object data' => [['data' => new \stdClass()]];
    }

    /**
     * @return array<string, mixed>
     */
    private function payment(string $id, ?string $payerEmail = null): array
    {
        $payment = [
            'id' => $id,
            'order' => [
                'formName' => 'Don de test',
                'date' => '2026-05-25T20:00:00+00:00',
            ],
            'paymentMeans' => 'Card',
            'amount' => 4242,
        ];

        if (null !== $payerEmail) {
            $payment['payer'] = ['email' => $payerEmail];
        }

        return $payment;
    }
}
