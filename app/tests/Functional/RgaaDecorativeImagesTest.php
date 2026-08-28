<?php

namespace App\Tests\Functional;

use App\Service\HelloAssoApiService;
use Symfony\Component\DomCrawler\Crawler;

class RgaaDecorativeImagesTest extends WebTestCase
{
    public function testHomepageDecorativeImagesAreHiddenFromAssistiveTechnologies(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $decorativeImages = [
            'immersion-group.webp' => 1,
            'galerie-3d-GNUT.webp' => 1,
            'meteo3D.webp' => 1,
            'galerie-3d-immo.webp' => 1,
            'send_money.svg' => 1,
            'volunteer_activism.svg' => 1,
            // La valeur -1 autorise un nombre variable d’occurrences, mais exige qu’au moins une image soit présente.
            'linkedin.svg' => -1,
        ];

        foreach ($decorativeImages as $filename => $expectedCount) {
            $this->assertDecorativeImage(
                $crawler,
                sprintf('main img[src$="/images/webp/%s"]', $filename),
                $expectedCount
            );
        }
    }

    public function testAboutPageDecorativeImagesAreHiddenFromAssistiveTechnologies(): void
    {
        $crawler = $this->client->request('GET', '/aPropos');

        $this->assertResponseIsSuccessful();

        foreach ([
            'immersion-decouverte.webp',
            'mask.svg',
            'benevoles-3.webp',
            'benevoles-2.webp',
            'benevoles-1.webp',
        ] as $filename) {
            $this->assertDecorativeImage(
                $crawler,
                sprintf('main img[src$="/images/webp/%s"]', $filename)
            );
        }
    }

    public function testAllDonationPageImagesAreDecorative(): void
    {
        $crawler = $this->client->request('GET', '/don');

        $this->assertResponseIsSuccessful();
        $this->assertAllImagesAreDecorative($crawler->filter('main img'), 2, 'Don');
    }

    public function testAllEventsPageImagesAreDecorative(): void
    {
        $helloAssoApiService = new class extends HelloAssoApiService {
            public function __construct()
            {
            }

            public function makeApiCall(string $url, array $headers = [], string $method = 'GET'): mixed
            {
                return [
                    'data' => [[
                        'title' => 'Événement de test',
                        'description' => 'Description de test',
                        'formType' => 'Event',
                        'formSlug' => 'evenement-de-test',
                        'widgetFullUrl' => 'https://example.test/widget',
                        'endDate' => '2099-12-31T23:59:59+0000',
                        'state' => 'Public',
                    ]],
                ];
            }
        };

        $this->client->getContainer()->set(HelloAssoApiService::class, $helloAssoApiService);
        $crawler = $this->client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertAllImagesAreDecorative($crawler->filter('main img'), 2, 'Évènements');
    }

    public function testInformativeImagesKeepTheirTextAlternatives(): void
    {
        $homeCrawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertInformativeImage(
            $homeCrawler,
            'apf_251112_001.webp',
            'Personne en fauteuil roulant portant un casque de réalité virtuelle'
        );
        $this->assertInformativeImage(
            $homeCrawler,
            'apf_251112_002.webp',
            'Membre de l’association GNUT06 plaçant un casque de réalité virtuelle pour une personne en fauteuil roulant'
        );

        $teamPortraits = $homeCrawler->filter('section[aria-labelledby="team-title"] img.card-img-top');
        self::assertGreaterThan(0, $teamPortraits->count(), 'Au moins un portrait de membre de l’équipe doit être présent.');

        foreach ($teamPortraits as $portrait) {
            self::assertInstanceOf(\DOMElement::class, $portrait);
            self::assertStringStartsWith('Portrait de ', $portrait->getAttribute('alt'));
            self::assertFalse($portrait->hasAttribute('aria-hidden'), 'Les portraits de l’équipe doivent rester restitués.');
        }

        $aboutCrawler = $this->client->request('GET', '/aPropos');

        $this->assertResponseIsSuccessful();
        $this->assertInformativeImage($aboutCrawler, 'gerald.webp');
    }

    private function assertDecorativeImage(Crawler $crawler, string $selector, int $expectedCount = 1): void
    {
        $images = $crawler->filter($selector);

        // La valeur -1 autorise un nombre variable d’occurrences, mais exige qu’au moins une image soit présente.
        if ($expectedCount === -1) {
            self::assertGreaterThan(0, $images->count(), sprintf('Au moins une image décorative est attendue pour le sélecteur "%s".', $selector));
        } else {
            self::assertCount($expectedCount, $images, sprintf('%d image(s) décorative(s) attendue(s) pour le sélecteur "%s".', $expectedCount, $selector));
        }

        foreach ($images as $image) {
            self::assertInstanceOf(\DOMElement::class, $image);

            self::assertSame('', $image->getAttribute('alt'), sprintf('Chaque image "%s" doit avoir une alternative vide.', $selector));
            self::assertSame('true', $image->getAttribute('aria-hidden'), sprintf('Chaque image "%s" doit être masquée aux technologies d’assistance.', $selector));
            self::assertFalse($image->hasAttribute('role'), sprintf('Chaque image "%s" ne doit pas forcer un rôle img.', $selector));
        }
    }

    private function assertAllImagesAreDecorative(Crawler $images, int $expectedCount, string $pageName): void
    {
        self::assertCount($expectedCount, $images, sprintf('Le nombre d’images attendu sur la page %s a changé.', $pageName));

        foreach ($images as $image) {
            self::assertInstanceOf(\DOMElement::class, $image);

            self::assertSame('', $image->getAttribute('alt'), sprintf('Toutes les images de la page %s doivent avoir une alternative vide.', $pageName));
            self::assertSame('true', $image->getAttribute('aria-hidden'), sprintf('Toutes les images de la page %s doivent être masquées aux technologies d’assistance.', $pageName));
            self::assertFalse($image->hasAttribute('role'), sprintf('Les images décoratives de la page %s ne doivent pas forcer un rôle img.', $pageName));
        }
    }

    private function assertInformativeImage(Crawler $crawler, string $filename, ?string $expectedAlternative = null): void
    {
        $selector = sprintf('main img[src$="/images/webp/%s"]', $filename);
        $image = $crawler->filter($selector);

        self::assertCount(1, $image, sprintf('L’image informative "%s" doit être présente.', $filename));
        if ($expectedAlternative === null) {
            self::assertNotSame('', $image->attr('alt'), sprintf('L’image informative "%s" doit conserver son alternative.', $filename));
        } else {
            self::assertSame($expectedAlternative, $image->attr('alt'), sprintf('L’alternative de l’image informative "%s" doit être exacte.', $filename));
        }
        self::assertNull($image->attr('aria-hidden'), sprintf('L’image informative "%s" doit rester restituée.', $filename));
    }
}
