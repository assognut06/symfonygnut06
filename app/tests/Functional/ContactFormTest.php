<?php

namespace App\Tests\Functional;

/**
 * Scenario-based tests for the public contact form.
 */
class ContactFormTest extends WebTestCase
{
    public function testContactPageLoads(): void
    {
        $this->client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#form-title');
        $this->assertSelectorExists('form[action="/contact"]');
    }

    public function testSuccessfulContactSubmissionShowsSuccessMessage(): void
    {
        $crawler = $this->client->request('GET', '/contact');
        $this->submitContactForm($crawler, $this->validContactPayload());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert.alert-success[role="alert"]', 'Votre message a bien été envoyé');
        $this->assertSelectorNotExists('.alert.alert-danger[role="alert"]');
    }

    public function testContactFormWithEmptyFieldsShowsValidationErrors(): void
    {
        $crawler = $this->client->request('GET', '/contact');
        $this->submitContactForm($crawler, [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'message' => '',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-danger', 'Veuillez renseigner votre prénom');
        $this->assertSelectorTextContains('.alert-danger', 'Veuillez renseigner votre nom');
        $this->assertSelectorTextContains('.alert-danger', 'Veuillez renseigner votre email');
        $this->assertSelectorNotExists('.alert-success');
    }

    public function testContactFormWithInvalidEmailShowsError(): void
    {
        $crawler = $this->client->request('GET', '/contact');
        $payload = $this->validContactPayload();
        $payload['email'] = 'not-an-email';
        $this->submitContactForm($crawler, $payload);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-danger', "Cette adresse email n'est pas valide");
        $this->assertSelectorNotExists('.alert-success');
    }

    public function testContactFormWithShortMessageShowsError(): void
    {
        $crawler = $this->client->request('GET', '/contact');
        $payload = $this->validContactPayload();
        $payload['message'] = 'trop cour';
        $this->submitContactForm($crawler, $payload);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-danger', 'Le message doit contenir au moins');
        $this->assertSelectorNotExists('.alert-success');
    }

    /**
     * @param array<string, string> $data
     */
    private function submitContactForm($crawler, array $data): void
    {
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/contact', array_merge(['_token' => $token], $data));
    }

    /**
     * @return array<string, string>
     */
    private function validContactPayload(): array
    {
        return [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean.dupont@test.com',
            'tel' => '0612345678',
            'project_type' => 'site_web',
            'message' => 'Demande de devis pour notre association.',
        ];
    }
}
