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
                ],
                'pagination' => ['totalCount' => 3],
            ]);
        static::getContainer()->set(HelloAssoApiService::class, $helloAssoApi);

        $this->client->request('GET', '/profil/payments/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#profile-content', 'owned-payment');
        $this->assertSelectorTextNotContains('#profile-content', 'victim-payment');
        $this->assertSelectorTextNotContains('#profile-content', 'payment-without-owner');
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
