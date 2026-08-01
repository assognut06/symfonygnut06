<?php

namespace App\Tests\Functional;

use Symfony\Component\DomCrawler\Crawler;

final class AccessibilityNavigationTest extends WebTestCase
{
    /**
     * @dataProvider auditedPageProvider
     */
    public function testAuditedPageHasExpectedLandmarksAndTabOrder(string $url, array $skipTargets): void
    {
        $crawler = $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('main'));
        $this->assertCount(1, $crawler->filter('main#main-content[tabindex="-1"]'));
        $this->assertCount(1, $crawler->filter('header[role="banner"]'));
        $this->assertCount(1, $crawler->filter('.skip-links a.skip-link[href="#main-content"]'));
        $this->assertCount(
            0,
            $crawler->filterXPath('//*[@tabindex and number(@tabindex) > 0]'),
            sprintf('The audited page "%s" must not contain a positive tabindex.', $url)
        );

        $firstInteractive = $crawler->filterXPath(
            '//body//*[self::a[@href] or self::button or self::input[not(@type="hidden")] or self::select or self::textarea or @tabindex]'
        )->first();

        $this->assertSame('a', $firstInteractive->nodeName());
        $this->assertTrue($firstInteractive->matches('.skip-link[href="#main-content"]'));
        $this->assertSame('Aller au contenu principal', trim($firstInteractive->text()));

        $this->assertCount(
            1,
            $crawler->filterXPath('//header[@role="banner"]/following::button[@id="openChat"]'),
            'The AI help button must follow the main navigation.'
        );
        $this->assertCount(
            1,
            $crawler->filterXPath('//button[@id="openChat"]/following::main[@id="main-content"]'),
            'The AI help button must precede the main content.'
        );

        $this->assertSkipTargetsAreFunctional($crawler, $skipTargets);
    }

    public static function auditedPageProvider(): iterable
    {
        yield 'Home' => ['/', ['#main-content']];
        yield 'Legal notice' => ['/mentions-legales', ['#main-content']];
        yield 'Contact' => ['/contact', ['#main-content', '#services']];
        yield 'Login' => ['/login', ['#main-content', '#connexion-form', '#alt-login']];
        yield 'About' => ['/aPropos', ['#main-content']];
        yield 'Donation' => ['/don', ['#main-content', '#appel-don', '#pourquoi-donner', '#form-don']];
        yield 'Events' => ['/evenements', ['#main-content']];
    }

    private function assertSkipTargetsAreFunctional(Crawler $crawler, array $skipTargets): void
    {
        foreach ($skipTargets as $targetSelector) {
            $this->assertCount(
                1,
                $crawler->filter(sprintf('.skip-links a.skip-link[href="%s"]', $targetSelector)),
                sprintf('Missing skip link targeting "%s".', $targetSelector)
            );

            $target = $crawler->filter($targetSelector);

            $this->assertCount(1, $target, sprintf('Missing skip-link target "%s".', $targetSelector));
            $this->assertSame(
                '-1',
                $target->attr('tabindex'),
                sprintf('The target "%s" must be programmatically focusable.', $targetSelector)
            );
        }
    }
}
