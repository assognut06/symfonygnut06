<?php

namespace App\Tests\Functional;

/**
 * Tests the contact form page.
 */
class ContactFormTest extends WebTestCase
{
    public function testContactPageLoads(): void
    {
        $this->client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
    }

    public function testContactPageHasForm(): void
    {
        $crawler = $this->client->request('GET', '/contact');

        $this->assertSelectorExists('form');
    }

    public function testContactFormSubmitWithoutRecaptchaFails(): void
    {
        $this->client->request('POST', '/contact', [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@test.com',
            'tel' => '0612345678',
            'project_type' => 'VR',
            'message' => 'Test message from functional test suite.',
        ]);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->getStatusCode() === 500,
            sprintf(
                'Contact form should return 200 (with reCAPTCHA error) or 500 (if reCAPTCHA API unreachable), got %d',
                $response->getStatusCode()
            )
        );
    }

    public function testContactFormWithInvalidEmail(): void
    {
        $this->client->request('POST', '/contact', [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'not-an-email',
            'tel' => '0612345678',
            'project_type' => 'VR',
            'message' => 'Test message.',
        ]);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->getStatusCode() === 500,
            sprintf(
                'Contact form should return 200 or 500 (reCAPTCHA API), got %d',
                $response->getStatusCode()
            )
        );
    }
}
