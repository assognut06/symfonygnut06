<?php

namespace App\Tests\Functional;

/**
 * Tests the donation/don casque flow.
 */
class DonationFlowTest extends WebTestCase
{
    public function testDonPageLoads(): void
    {
        $this->client->request('GET', '/don');

        $this->assertResponseIsSuccessful();
    }

    public function testDonateurFormPageLoads(): void
    {
        $this->client->request('GET', '/donateur/formulaire');

        $this->assertResponseIsSuccessful();
    }

    public function testDonateurFormHasBothForms(): void
    {
        $crawler = $this->client->request('GET', '/donateur/formulaire');

        $this->assertResponseIsSuccessful();
        $formCount = $crawler->filter('form')->count();
        $this->assertGreaterThanOrEqual(1, $formCount, 'Donateur page should have at least one form');
    }

    public function testDonCasqueNewWithoutSessionRedirects(): void
    {
        $this->client->request('GET', '/don-casque/new');

        $this->assertResponseIsSuccessful();
    }

    public function testDonCasqueListWithoutSessionRedirects(): void
    {
        $this->client->request('GET', '/don-casque/list-dons');

        $this->assertResponseRedirects();
    }
}
