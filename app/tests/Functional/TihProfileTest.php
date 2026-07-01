<?php

namespace App\Tests\Functional;

/**
 * Tests the TIH (espace-tih) profile management for logged-in users.
 */
class TihProfileTest extends WebTestCase
{
    public function testTihSpaceRedirectsAnonymous(): void
    {
        $this->client->request('GET', '/espace-tih');

        $this->assertResponseRedirects();
    }

    public function testTihSpaceAccessibleByLoggedInUser(): void
    {
        $this->loginAsNewUser();

        $this->client->request('GET', '/espace-tih');

        $this->assertResponseIsSuccessful();
    }

    public function testTihSpaceShowsFormForNewUser(): void
    {
        $this->loginAsNewUser();

        $crawler = $this->client->request('GET', '/espace-tih');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testTihSpaceShowsProfileForTihUser(): void
    {
        $tihUser = $this->createTihUser();
        $this->loginAs($tihUser);

        $crawler = $this->client->request('GET', '/espace-tih');

        $this->assertResponseIsSuccessful();
    }

    public function testTihSpaceEditMode(): void
    {
        $tihUser = $this->createTihUser();
        $this->loginAs($tihUser);

        $crawler = $this->client->request('GET', '/espace-tih?edit=1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
