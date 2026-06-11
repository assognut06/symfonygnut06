<?php

namespace App\Tests\Functional;

/**
 * Verifies admin routes are protected: anonymous users get redirected,
 * regular users get 403, and admins can access the pages.
 */
class AdminAccessControlTest extends WebTestCase
{
    /**
     * @dataProvider adminUrlAllProvider
     */
    public function testAdminRouteRedirectsAnonymousToLogin(string $url): void
    {
        $this->client->request('GET', $url);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isRedirection(),
            sprintf('Admin URL "%s" should redirect anonymous users, got %d.', $url, $response->getStatusCode())
        );
    }

    /**
     * @dataProvider adminUrlAllProvider
     */
    public function testAdminRouteDeniesRegularUser(string $url): void
    {
        $this->loginAsNewUser();

        $this->client->request('GET', $url);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->getStatusCode() === 403 || $response->isRedirection(),
            sprintf('Admin URL "%s" should deny regular user, got %d.', $url, $response->getStatusCode())
        );
    }

    /**
     * @dataProvider adminUrlProvider
     */
    public function testAdminRouteAccessibleByAdmin(string $url): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', $url);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirection(),
            sprintf('Admin URL "%s" should be accessible by admin, got %d.', $url, $response->getStatusCode())
        );
    }

    public static function adminUrlProvider(): iterable
    {
        yield 'Dashboard' => ['/admin'];
        yield 'Users' => ['/admin/user'];
        yield 'TIH list' => ['/admin/tih'];
        yield 'Donations' => ['/admin/dons'];
    }

    public static function adminUrlAllProvider(): iterable
    {
        yield 'Dashboard' => ['/admin'];
        yield 'Users' => ['/admin/user'];
        yield 'TIH list' => ['/admin/tih'];
        yield 'Donations' => ['/admin/dons'];
    }

    public function testAdminTihValidateRequiresCSRF(): void
    {
        $admin = $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();

        $tih->setIsValidate(false);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/validate/' . $tih->getId(), [
            '_token' => 'invalid_csrf_token',
        ]);

        $this->em->refresh($tih);
        $this->assertFalse(
            $tih->isValidate(),
            'TIH should not be validated with invalid CSRF token'
        );
    }
}
