<?php

namespace App\Tests\Functional;

/**
 * Scenario-based tests for TIH search: fixtures in DB, then assert filtered results.
 */
class TihSearchTest extends WebTestCase
{
    public function testTihSearchPageLoads(): void
    {
        $this->client->request('GET', '/tih/tih_search');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Recherche de Profils TIH');
    }

    public function testSearchFiltersByRegionReturnsOnlyMatchingProfiles(): void
    {
        $this->createSearchableTih([
            'email' => 'nice-paca@test.com',
            'firstName' => 'Alice',
            'lastName' => 'Paca',
            'city' => 'Nice',
            'region' => 'PACA',
        ]);
        $this->createSearchableTih([
            'email' => 'paris-idf@test.com',
            'firstName' => 'Bruno',
            'lastName' => 'Idf',
            'city' => 'Paris',
            'region' => 'Île-de-France',
        ]);

        $this->client->request('GET', '/tih/tih_search', ['regions' => ['PACA']]);

        $this->assertResponseIsSuccessful();
        $this->assertTihResultCount(1);
        $this->assertTihGridContains('Alice Paca');
        $this->assertTihGridNotContains('Bruno Idf');
    }

    public function testSearchExcludesUnvalidatedProfiles(): void
    {
        $this->createSearchableTih([
            'email' => 'validated@test.com',
            'firstName' => 'Valid',
            'lastName' => 'Profile',
            'validated' => true,
        ]);
        $this->createSearchableTih([
            'email' => 'pending@test.com',
            'firstName' => 'Pending',
            'lastName' => 'Review',
            'validated' => false,
        ]);

        $this->client->request('GET', '/tih/tih_search');

        $this->assertResponseIsSuccessful();
        $this->assertTihResultCount(1);
        $this->assertTihGridContains('Valid Profile');
        $this->assertTihGridNotContains('Pending Review');
    }

    public function testSearchFiltersByRateRange(): void
    {
        $this->createSearchableTih([
            'email' => 'affordable@test.com',
            'firstName' => 'Low',
            'lastName' => 'Rate',
            'rate' => '200.00',
            'rateType' => 'daily',
        ]);
        $this->createSearchableTih([
            'email' => 'expensive@test.com',
            'firstName' => 'High',
            'lastName' => 'Rate',
            'rate' => '600.00',
            'rateType' => 'daily',
        ]);

        $this->client->request('GET', '/tih/tih_search', [
            'minRate' => '100',
            'maxRate' => '500',
            'rateType' => 'daily',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertTihResultCount(1);
        $this->assertTihGridContains('Low Rate');
        $this->assertTihGridNotContains('High Rate');
    }

    public function testSearchFiltersByCompetence(): void
    {
        $php = $this->createCompetence('PHP');
        $design = $this->createCompetence('Design');

        $this->createSearchableTih([
            'email' => 'dev@test.com',
            'firstName' => 'Dev',
            'lastName' => 'Backend',
            'competences' => [$php],
        ]);
        $this->createSearchableTih([
            'email' => 'designer@test.com',
            'firstName' => 'Dev',
            'lastName' => 'Frontend',
            'competences' => [$design],
        ]);

        $this->client->request('GET', '/tih/tih_search', [
            'skills' => [$php->getId()],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertTihResultCount(1);
        $this->assertTihGridContains('Dev Backend');
        $this->assertTihGridNotContains('Dev Frontend');
    }

    public function testTihDetailsWithInvalidId(): void
    {
        $this->client->request('GET', '/tih/tih/999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testTihDetailsShowsProfileInformation(): void
    {
        $tih = $this->createSearchableTih([
            'email' => 'detail@test.com',
            'firstName' => 'Marie',
            'lastName' => 'Curie',
            'city' => 'Nice',
            'region' => 'PACA',
        ]);

        $this->client->request('GET', '/tih/tih/' . $tih->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Marie');
        $this->assertSelectorTextContains('body', 'Curie');
        $this->assertSelectorTextContains('body', 'Nice');
    }

    public function testTihContactPageShowsFormForValidatedProfile(): void
    {
        $tih = $this->createSearchableTih([
            'email' => 'contact@test.com',
            'firstName' => 'Contact',
            'lastName' => 'Me',
        ]);

        $crawler = $this->client->request('GET', '/tih/tih/' . $tih->getId() . '/contact');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Contact Me');
        $this->assertSelectorExists('form');
    }
}
