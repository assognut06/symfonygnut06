<?php

namespace App\Tests\Functional;

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
        $crawler = $this->client->request('GET', '/reset-password');

        $this->assertSelectorExists('form');
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
        $crawler = $this->client->request('GET', '/reset-password');

        $form = $crawler->filter('form')->form();
        $form['reset_password_request_form[email]'] = 'nonexistent@test.com';

        $this->client->submit($form);

        $this->assertResponseRedirects(
            null,
            null,
            'Should redirect even for non-existent emails to prevent user enumeration'
        );
    }
}
