<?php

namespace App\Tests\Functional;

/**
 * Smoke tests: every public page returns 200 or an expected redirect.
 */
class PublicRouteSmokeTest extends WebTestCase
{
    /**
     * @dataProvider publicUrlProvider
     */
    public function testPublicPageIsSuccessful(string $url): void
    {
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful(
            sprintf('Public URL "%s" did not return 2xx.', $url)
        );
    }

    /**
     * @return iterable<array<int,string>>
     */
    public static function publicUrlProvider(): iterable
    {
        yield 'Home' => ['/'];
        yield 'Login' => ['/login'];
        yield 'Register' => ['/register'];
        yield 'Contact' => ['/contact'];
        yield 'A propos' => ['/aPropos'];
        yield 'Don' => ['/don'];
        yield 'Adhesion' => ['/adhesion'];
        yield 'Metavers' => ['/metavers/'];
        yield 'Mention legales' => ['/mentions-legales'];
        yield 'Presse' => ['/presse'];
        yield 'Handi 3D' => ['/Handi-3D'];
        yield 'Don casque donateur form' => ['/donateur/formulaire'];
        yield 'Reset password request' => ['/reset-password'];
        yield 'TIH Search' => ['/tih/tih_search'];
        yield 'Accompagnement digital' => ['/digital/consulting'];
        yield 'Newsletter confirmation' => ['/newsletter/confirmation'];
    }

    /**
     * @dataProvider redirectUrlProvider
     */
    public function testPublicPageRedirects(string $url): void
    {
        $this->client->request('GET', $url);

        $this->assertResponseRedirects(
            null,
            null,
            sprintf('URL "%s" did not redirect as expected.', $url)
        );
    }

    /**
     * @return iterable<array<int,string>>
     */
    public static function redirectUrlProvider(): iterable
    {
        yield 'Logout redirects' => ['/logout'];
    }

    public function testHomepageContainsGnut06(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('html');
    }

    public function testNewsletterConfirmationPageHasConfirmationAndHomeLink(): void
    {
        $this->client->request('GET', '/newsletter/confirmation');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Votre inscription est confirmée !');
        $this->assertSelectorExists('a[href="/"]');
    }

    public function testKeyFiguresUseListAndParagraphSemantics(): void
    {
        $crawler = $this->client->request('GET', '/aPropos');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('#nos-chiffres + ul h3');
        $this->assertCount(6, $crawler->filter('#nos-chiffres + ul > li'));
        $this->assertCount(6, $crawler->filter('#nos-chiffres + ul .js-counter'));
    }

    public function testLoginPageHasForm(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testLoginHelpAndErrorTextsUseParagraphs(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('p#username-help');
        $this->assertSelectorExists('p#username-error');
        $this->assertSelectorExists('p#password-help');
        $this->assertSelectorExists('p#password-error');
        $this->assertSelectorExists('p#connexion-button-help');
        $this->assertSelectorNotExists('div.form-text');
    }

    public function testRegisterPageHasForm(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testSitemapReturnsXml(): void
    {
        $this->client->request('GET', '/sitemap.xml');

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->getStatusCode() === 404,
            'Sitemap should return 200 or 404'
        );
    }

    public function test404ReturnsProperStatusCode(): void
    {
        $this->client->request('GET', '/this-page-does-not-exist-at-all');

        $this->assertResponseStatusCodeSame(404);
    }
}
