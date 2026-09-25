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

    /**
     * @dataProvider paymentPaginationProvider
     */
    public function testPaymentsPagination(int $page, int $totalPages, bool $emptyPage): void
    {
        $this->loginAs($this->createUser('member@example.test'));
        $helloAssoApi = $this->createMock(HelloAssoApiService::class);
        $helloAssoApi->expects($this->once())
            ->method('makeApiCall')
            ->with($this->callback(static function (string $url) use ($page): bool {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

                return (string) $page === ($query['pageIndex'] ?? null)
                    && 'member@example.test' === ($query['userSearchKey'] ?? null);
            }))
            ->willReturn([
                'data' => [$this->payment('payment', $emptyPage ? 'victim@example.test' : 'member@example.test')],
                'pagination' => ['pageIndex' => $page, 'totalPages' => $totalPages, 'totalCount' => 9],
            ]);
        static::getContainer()->set(HelloAssoApiService::class, $helloAssoApi);

        $this->client->request('GET', '/profil/payments/' . $page);

        $this->assertResponseIsSuccessful();
        $nav = 'nav[aria-label="Pagination des paiements"]';
        if (1 === $totalPages) {
            $this->assertSelectorNotExists($nav);

            return;
        }

        $this->assertSelectorTextContains($nav . ' [aria-current="page"]', (string) $page);
        $router = static::getContainer()->get('router');
        if (1 < $page) {
            $previousUrl = $router->generate('app_profil_page', ['donnees' => 'payments', 'page' => $page - 1]);
            $this->assertSelectorExists($nav . ' a[rel="prev"][href="' . $previousUrl . '"]');
        } else {
            $this->assertSelectorNotExists($nav . ' a[rel="prev"]');
        }
        if ($totalPages > $page) {
            $nextUrl = $router->generate('app_profil_page', ['donnees' => 'payments', 'page' => $page + 1]);
            $this->assertSelectorExists($nav . ' a[rel="next"][href="' . $nextUrl . '"]');
        } else {
            $this->assertSelectorNotExists($nav . ' a[rel="next"]');
        }
        if ($emptyPage) {
            $this->assertSelectorCount(0, '#profile-content [role="listitem"]');
        }
    }

    /**
     * @return iterable<string, array{int, int, bool}>
     */
    public static function paymentPaginationProvider(): iterable
    {
        yield 'first page' => [1, 3, false];
        yield 'middle page' => [2, 3, false];
        yield 'last page' => [3, 3, false];
        yield 'empty filtered page' => [2, 3, true];
        yield 'single page' => [1, 1, false];
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
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
