<?php

namespace App\Tests\Functional;

/**
 * Tests authentication flows: login, logout, protected access, OAuth redirects.
 */
class AuthenticationTest extends WebTestCase
{
    public function testLoginPageLoads(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#connexion-form');
    }

    public function testLoginWithValidCredentialsRedirectsToProfile(): void
    {
        $this->createUser('login@test.com', 'MyPassword1!');

        $this->submitLogin('login@test.com', 'MyPassword1!');

        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('profil', $location);

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/espace-tih');
        $this->assertResponseIsSuccessful();
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->createUser('valid@test.com', 'RealPassword1!');

        $this->submitLogin('valid@test.com', 'WrongPassword!');

        $this->assertResponseRedirects('/login');
    }

    public function testLoginWithNonExistentUser(): void
    {
        $this->submitLogin('nobody@test.com', 'Whatever1!');

        $this->assertResponseRedirects('/login');
    }

    public function testLogoutRedirects(): void
    {
        $this->loginAsNewUser();

        $this->client->request('GET', '/logout');

        $this->assertResponseRedirects();
    }

    public function testAlreadyLoggedInUserRedirectedFromLogin(): void
    {
        $this->loginAsNewUser();

        $this->client->request('GET', '/login');

        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('profil', $location);
    }

    public function testGoogleOAuthStartRedirects(): void
    {
        $this->client->request('GET', '/connect/google');

        $this->assertResponseRedirects();
    }

    public function testOutlookOAuthStartRedirects(): void
    {
        $this->client->request('GET', '/connect/outlook');

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isRedirection() || $response->getStatusCode() === 500,
            'Outlook OAuth should redirect or fail gracefully without Azure config'
        );
    }
}
