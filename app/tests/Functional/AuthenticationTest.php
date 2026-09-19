<?php

namespace App\Tests\Functional;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

    public function testRequiredLoginFieldsAreIdentifiedAndKeepTheirErrorsAssociated(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains(
            '#required-fields-info',
            'Toutes les informations sont obligatoires.'
        );
        $this->assertSelectorExists(
            '#connexion-form[aria-describedby~="required-fields-info"] '
            . '#username[required][aria-required="true"][aria-describedby~="username-error"]'
        );
        $this->assertSelectorExists(
            '#connexion-form[aria-describedby~="required-fields-info"] '
            . '#password[required][aria-required="true"][aria-describedby~="password-error"]'
        );
        $this->assertSelectorExists('#username-error.invalid-feedback[role="alert"]');
        $this->assertSelectorExists('#password-error.invalid-feedback[role="alert"]');
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
        $authorizationUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?stub=1';

        $oauthClient = $this->createMock(OAuth2ClientInterface::class);
        $oauthClient->expects($this->once())
            ->method('redirect')
            ->willReturnCallback(static function (array $scopes, array $options) use ($authorizationUrl): RedirectResponse {
                self::assertSame([], $scopes);
                self::assertArrayHasKey('nonce', $options);
                self::assertNotSame('', $options['nonce']);

                return new RedirectResponse($authorizationUrl);
            });

        $registry = $this->createMock(ClientRegistry::class);
        $registry->method('getClient')->with('azure')->willReturn($oauthClient);

        $this->client->disableReboot();
        static::getContainer()->set('knpu.oauth2.registry', $registry);

        $this->client->request('GET', '/connect/outlook');

        $this->assertResponseRedirects($authorizationUrl);
    }
}
