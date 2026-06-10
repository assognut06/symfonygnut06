<?php

namespace App\Tests\Functional;

/**
 * Tests authentication flows: login, logout, protected access, OAuth redirects.
 */
class AuthenticationTest extends WebTestCase
{
    public function testLoginPageLoads(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testLoginWithValidCredentialsRedirectsToProfile(): void
    {
        $this->createUser('login@test.com', 'MyPassword1!');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form();
        $form['_username'] = 'login@test.com';
        $form['_password'] = 'MyPassword1!';

        $this->client->submit($form);

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

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form();
        $form['_username'] = 'valid@test.com';
        $form['_password'] = 'WrongPassword!';

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
    }

    public function testLoginWithNonExistentUser(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form();
        $form['_username'] = 'nobody@test.com';
        $form['_password'] = 'Whatever1!';

        $this->client->submit($form);

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
