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

    public function testAdminUserPromoteRequiresCSRF(): void
    {
        $admin = $this->loginAsAdmin();
        $target = $this->createUser('target@test.com');

        $this->client->request('POST', '/admin/user/promote/' . $target->getId(), [
            '_token' => 'invalid_token',
        ]);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->getStatusCode() === 403 || $response->isRedirection(),
            'Promote with invalid CSRF should be denied'
        );

        $this->em->refresh($target);
        $this->assertNotContains(
            'ROLE_ADMIN',
            $target->getRoles(),
            'User should not be promoted with invalid CSRF token'
        );
    }

    public function testAdminUserDeleteRequiresCSRF(): void
    {
        $admin = $this->loginAsAdmin();
        $target = $this->createUser('todelete@test.com');

        $this->client->request('POST', '/admin/user/delete/' . $target->getId(), [
            '_method' => 'DELETE',
            '_token' => 'invalid_token',
        ]);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isRedirection() || $response->getStatusCode() === 403,
            'Delete with invalid CSRF should fail'
        );

        $stillExists = $this->em->getRepository(\App\Entity\User::class)->find($target->getId());
        $this->assertNotNull($stillExists, 'User should not be deleted with invalid CSRF token');
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
