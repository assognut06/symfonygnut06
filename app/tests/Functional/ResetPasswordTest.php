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
}
