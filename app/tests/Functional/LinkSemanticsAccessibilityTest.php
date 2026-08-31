<?php

namespace App\Tests\Functional;

final class LinkSemanticsAccessibilityTest extends WebTestCase
{
    public function testHomepageNavigationLinksKeepTheirNativeRole(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('main a[href]');
        $this->assertSelectorNotExists('main a[role="button"]');
    }

    public function testOAuthLinksKeepTheirNativeRole(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(2, '#alt-login a[href]');
        $this->assertSelectorNotExists('#alt-login a[role="button"]');
    }

    public function testJoinOurMissionLinksKeepTheirNativeRole(): void
    {
        $this->client->request('GET', '/aPropos');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(3, 'section[aria-labelledby="rejoignez-nous"] a[href]');
        $this->assertSelectorNotExists(
            'section[aria-labelledby="rejoignez-nous"] a[role="button"]'
        );
    }
}
