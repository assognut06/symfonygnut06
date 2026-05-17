<?php

namespace App\Tests\Functional;

/**
 * Tests the TIH (Travailleur Independant Handicape) search functionality.
 */
class TihSearchTest extends WebTestCase
{
    public function testTihSearchPageLoads(): void
    {
        $this->client->request('GET', '/tih/tih_search');

        $this->assertResponseIsSuccessful();
    }

    public function testTihSearchWithFilters(): void
    {
        $this->client->request('GET', '/tih/tih_search', [
            'regions' => ['PACA'],
            'page' => 1,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testTihSearchWithSkillFilter(): void
    {
        $this->client->request('GET', '/tih/tih_search', [
            'skills' => [1, 2],
            'page' => 1,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testTihSearchWithRateFilter(): void
    {
        $this->client->request('GET', '/tih/tih_search', [
            'minRate' => '100',
            'maxRate' => '500',
            'rateType' => 'daily',
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testTihSearchWithAvailabilityPeriod(): void
    {
        $this->client->request('GET', '/tih/tih_search', [
            'availabilityPeriod' => '1',
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testTihSearchPagination(): void
    {
        $this->client->request('GET', '/tih/tih_search', ['page' => 2]);

        $this->assertResponseIsSuccessful();
    }

    public function testTihSearchWithInvalidPage(): void
    {
        $this->client->request('GET', '/tih/tih_search', ['page' => -1]);

        $this->assertResponseIsSuccessful();
    }

    public function testTihDetailsWithInvalidId(): void
    {
        $this->client->request('GET', '/tih/tih/999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testTihDetailsWithValidId(): void
    {
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();

        $this->client->request('GET', '/tih/tih/' . $tih->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testTihContactPageLoads(): void
    {
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();

        $this->client->request('GET', '/tih/tih/' . $tih->getId() . '/contact');

        $this->assertResponseIsSuccessful();
    }

    public function testTihContactPageHasForm(): void
    {
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();

        $crawler = $this->client->request('GET', '/tih/tih/' . $tih->getId() . '/contact');

        $this->assertSelectorExists('form');
    }
}
