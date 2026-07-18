<?php

namespace App\Tests\Functional;

use Symfony\Component\Mime\RawMessage;

/**
 * Tests the password reset request flow.
 */
class ResetPasswordTest extends WebTestCase
{
    public function testResetPasswordRequestPageLoads(): void
    {
        $this->client->request('GET', '/reset-password');

        $this->assertResponseIsSuccessful();
    }

    public function testResetPasswordRequestHasForm(): void
    {
        $this->client->request('GET', '/reset-password');

        $this->assertResponseHtmlContainsForm();
    }

    public function testResetPasswordCheckEmailPageLoads(): void
    {
        $this->client->request('GET', '/reset-password/check-email');

        $this->assertResponseIsSuccessful();
    }

    public function testResetPasswordWithInvalidTokenRedirects(): void
    {
        $this->client->request('GET', '/reset-password/reset/invalid-token-12345');

        $this->assertResponseRedirects();
    }

    public function testResetPasswordRequestWithNonExistentEmailStillRedirects(): void
    {
        $this->submitResetPasswordRequest('nonexistent@test.com');

        $this->assertResponseRedirects(
            null,
            null,
            'Should redirect even for non-existent emails to prevent user enumeration'
        );
    }

    /**
     * @dataProvider safeResetPasswordHostsProvider
     */
    public function testResetPasswordEmailUsesSafeRequestHostForTokenLink(string $host): void
    {
        $email = $this->requestResetPasswordEmailFromHost(
            sprintf('reset-host-%s@test.com', preg_replace('/[^a-z0-9]+/', '-', $host)),
            $host
        );

        self::assertNotNull($email);
        self::assertEmailHtmlBodyContains($email, sprintf('https://%s/reset-password/reset/', $host));
    }

    public function testResetPasswordEmailDoesNotUseUntrustedRequestHostForTokenLink(): void
    {
        $victimEmail = 'reset-host-victim@test.com';
        $untrustedHost = 'attacker.example';

        $email = $this->requestResetPasswordEmailFromHost($victimEmail, $untrustedHost);

        if (null === $email) {
            return;
        }

        self::assertEmailHtmlBodyNotContains($email, $untrustedHost);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function safeResetPasswordHostsProvider(): array
    {
        return [
            'dev host' => ['dev.gnut06.org'],
            'localhost' => ['localhost'],
            'loopback ip' => ['127.0.0.1'],
            'www host' => ['www.gnut06.org'],
        ];
    }

    private function requestResetPasswordEmailFromHost(string $victimEmail, string $host): ?RawMessage
    {
        $this->createUser($victimEmail);
        $this->client->setServerParameters([
            'HTTP_HOST' => $host,
            'HTTPS' => true,
        ]);

        $this->client->request('GET', '/reset-password');

        if (400 === $this->client->getResponse()->getStatusCode()) {
            self::assertEmailCount(0);

            return null;
        }

        $this->assertResponseIsSuccessful();

        $this->client->submitForm("Envoyer l'email de réinitialisation du mot de passe", [
            'reset_password_request_form[email]' => $victimEmail,
        ]);

        $this->assertResponseRedirects();
        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);
        self::assertNotNull($email);
        self::assertEmailAddressContains($email, 'to', $victimEmail);
        self::assertEmailHtmlBodyContains($email, '/reset-password/reset/');

        return $email;
    }
}
