<?php

namespace App\Tests\Functional;

use App\Entity\Payers;
use App\Entity\Tih;
use App\Entity\TihApplicationEvent;
use App\Service\HelloAssoApiService;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Twig\Environment;

class ProfileUiTest extends WebTestCase
{
    /** @dataProvider accountPageProvider */
    public function testAccountControlsAndIdentifierRemainAvailable(string $url, bool $linked): void
    {
        $user = $this->loginAsNewUser();
        if ($linked) {
            $user->setGoogleId('google-review-id');
            $user->setAzureId('microsoft-review-id');
            $this->em->flush();
        }

        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.account-hero__meta', 'Identifiant : '.$user->getId());
        $this->assertSelectorTextContains('#oauth-links-title', 'Comptes connectés');
        $this->assertSelectorTextContains('[aria-labelledby="tih-title"]', "S'inscrire comme TIH");
        $router = static::getContainer()->get('router');
        $this->assertSelectorExists('.account-page a[href="'.$router->generate('espace_tih').'"]');
        foreach (['google', 'microsoft'] as $provider) {
            $selector = '.account-page form[method="post"][action="'.$router->generate('oauth_link_reauth', ['provider' => $provider]).'"]';
            if ($linked) {
                $this->assertSelectorNotExists($selector);
            } else {
                $this->assertSelectorExists($selector);
                $this->assertSelectorExists($selector.' input[name="_token"][value]');
            }
        }
        $this->assertSelectorNotExists('.account-links a[href="'.$router->generate('connect_google_start').'"]');
        $this->assertSelectorNotExists('.account-links a[href="'.$router->generate('connect_outlook_start').'"]');
        $this->assertSelectorCount($linked ? 2 : 0, '.account-links .account-badge');
        $this->assertSelectorCount('/profil' === $url ? 1 : 0, 'form[action="/profil"]');
    }

    /** @return iterable<string, array{string, bool}> */
    public static function accountPageProvider(): iterable
    {
        foreach (['profile' => '/profil', 'orders' => '/profil/orders', 'payments' => '/profil/payments'] as $section => $url) {
            yield $section.' unlinked' => [$url, false];
            yield $section.' linked' => [$url, true];
        }
    }

    public function testTihMemberCanAccessTheirSpaceFromHistory(): void
    {
        $this->loginAs($this->createTihUser('profile-ui-tih@example.test'));
        $this->client->request('GET', '/profil/orders');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('[aria-labelledby="tih-title"]', 'Accéder à mon espace TIH');
    }

    public function testProfileAccessibilityReferencesResolve(): void
    {
        $this->loginAsNewUser();
        $crawler = $this->client->request('GET', '/profil');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(1, '.account-page h1');
        $this->assertSelectorCount(1, '.account-nav [aria-current="page"]');
        $ids = [];
        foreach ($crawler->filter('.account-page [id]') as $node) {
            assert($node instanceof \DOMElement);
            $id = $node->getAttribute('id');
            $this->assertNotContains($id, $ids, 'Duplicate profile id: '.$id);
            $ids[] = $id;
        }
        foreach ($crawler->filter('.account-page [aria-labelledby], .account-page [aria-describedby]') as $node) {
            assert($node instanceof \DOMElement);
            foreach (['aria-labelledby', 'aria-describedby'] as $attribute) {
                foreach (preg_split('/\s+/', trim($node->getAttribute($attribute))) as $id) {
                    if ('' !== $id) {
                        $this->assertCount(1, $crawler->filter('[id="'.$id.'"]'), 'Missing accessibility target: '.$id);
                    }
                }
            }
        }
    }

    /** @dataProvider applicationNoticeProvider */
    public function testProfileRetainsTheLatestApplicationNotice(bool $reviewIsLatest, bool $approved): void
    {
        $user = $this->createTihUser('profile-notice@example.test');
        $tih = $user->getTih();
        $refusal = new TihApplicationEvent($tih, Tih::STATUS_REFUSED, $user, 'Ancien refus.', TihApplicationEvent::SOURCE_ADMIN_DECISION);
        $review = new TihApplicationEvent($tih, Tih::STATUS_PENDING, $user, 'Nouvelle demande de document.', TihApplicationEvent::SOURCE_ADMIN_REVIEW_REQUEST);
        $occurredAt = new \ReflectionProperty(TihApplicationEvent::class, 'occurredAt');
        $occurredAt->setValue($refusal, new \DateTimeImmutable('2026-10-20 12:00:00'));
        $occurredAt->setValue($review, new \DateTimeImmutable('2026-10-20 12:00:00'));
        foreach ($reviewIsLatest ? [$refusal, $review] : [$review, $refusal] as $event) {
            $tih->addApplicationEvent($event);
            $this->em->persist($event);
            $this->em->flush();
        }
        $tih->setApplicationStatus($approved ? Tih::STATUS_APPROVED : ($reviewIsLatest ? Tih::STATUS_PENDING : Tih::STATUS_REFUSED));
        $this->em->flush();
        $this->loginAs($user);

        $this->client->request('GET', '/profil');

        $this->assertResponseIsSuccessful();
        if ($approved) {
            $this->assertSelectorNotExists('#tih-review-message');
        } else {
            $this->assertSelectorTextContains('#tih-review-message', $reviewIsLatest ? 'Nouvelle demande de document.' : 'Ancien refus.');
            $this->assertSelectorTextNotContains('#tih-review-message', $reviewIsLatest ? 'Ancien refus.' : 'Nouvelle demande de document.');
            $this->assertSelectorNotExists('a[href="#tih-review-message"]');
        }
    }

    /** @return iterable<string, array{bool, bool}> */
    public static function applicationNoticeProvider(): iterable
    {
        yield 'latest review at the same timestamp' => [true, false];
        yield 'latest refusal at the same timestamp' => [false, false];
        yield 'approved profile hides old notices' => [true, true];
    }

    /** @dataProvider fieldValidityProvider */
    public function testFieldRenderingPreservesConfiguredClassesAndAttributes(bool $invalid): void
    {
        $form = static::getContainer()->get('form.factory')
            ->createBuilder(FormType::class, null, ['csrf_protection' => false])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'class' => 'form-control js-address-lookup',
                    'autocomplete' => 'street-address',
                    'aria-describedby' => 'address-error',
                    'data-controller' => 'address',
                ],
            ])->getForm();
        $form->submit(['address' => '1 rue de test']);
        if ($invalid) {
            $form->get('address')->addError(new FormError('Adresse invalide.'));
        }
        $html = static::getContainer()->get(Environment::class)->createTemplate(
            '{% import "profil/_fields.html.twig" as ui %}{{ ui.field(field) }}'
        )->render(['field' => $form->get('address')->createView()]);
        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('input.form-control.js-address-lookup[autocomplete="street-address"][data-controller="address"][aria-describedby="address-error"]'));
        $classes = preg_split('/\s+/', $crawler->filter('input')->attr('class'));
        $this->assertSame(1, count(array_keys($classes, 'form-control', true)));
        $this->assertCount($invalid ? 1 : 0, $crawler->filter('input.is-invalid'));
        if ($invalid) {
            $this->assertStringContainsString('Adresse invalide.', $crawler->filter('#address-error')->text());
        }
    }

    /** @return iterable<string, array{bool}> */
    public static function fieldValidityProvider(): iterable
    {
        yield 'valid field' => [false];
        yield 'invalid field' => [true];
    }

    /** @dataProvider historySectionProvider */
    public function testHistoryRetainsIdentifiersPaymentMethodAndTimestampSeconds(string $section): void
    {
        $user = $this->loginAsNewUser();
        $api = $this->createMock(HelloAssoApiService::class);
        $api->method('makeApiCall')->willReturn([
            'data' => [[
                'id' => 'payment-42',
                'order' => ['id' => 42, 'formName' => 'Atelier VR', 'date' => '2026-10-10T10:34:56+00:00'],
                'name' => 'Billet standard', 'amount' => 1500, 'paymentMeans' => 'Card',
                'payer' => ['email' => $user->getEmail()],
            ]],
            'pagination' => ['pageIndex' => 2, 'totalPages' => 3],
        ]);
        static::getContainer()->set(HelloAssoApiService::class, $api);
        $this->client->request('GET', '/profil/'.$section.'/2');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.account-list', 'Atelier VR');
        $this->assertSelectorTextContains('.account-list', 'Billet standard');
        $this->assertSelectorTextContains('.account-row__meta', '12h34:56');
        $this->assertSelectorTextContains('.account-row__meta', 'payments' === $section ? 'Paiement n°payment-42' : 'Commande n°42');
        $this->assertSelectorTextContains('.account-row__amount', '15');
        if ('payments' === $section) {
            $this->assertSelectorTextContains('.account-row__meta', 'Card');
        }
        $router = static::getContainer()->get('router');
        foreach (['prev' => 1, 'next' => 3] as $rel => $page) {
            $url = $router->generate('app_profil_page', ['donnees' => $section, 'page' => $page]);
            $this->assertSelectorExists('.pagination a[rel="'.$rel.'"][href="'.$url.'"]');
        }
    }

    /** @return iterable<string, array{string}> */
    public static function historySectionProvider(): iterable
    {
        yield 'orders' => ['orders'];
        yield 'payments' => ['payments'];
    }

    public function testProfileSavePersistsAndShowsConfirmation(): void
    {
        $this->loginAsNewUser();
        $crawler = $this->client->request('GET', '/profil');
        $form = $crawler->selectButton('Enregistrer')->form([
            'profile[firstName]' => 'Alex', 'profile[lastName]' => 'Review',
            'profile[address]' => '1 rue de test', 'profile[zipCode]' => '06000',
            'profile[city]' => 'Nice', 'profile[phone]' => '06 12 34 56 78',
        ]);
        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);

        $this->assertResponseRedirects('/profil');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('[role="alert"]', 'Vos informations ont été mises à jour avec succès');
        $this->assertSelectorTextContains('#account-title', 'Alex');
        $this->assertSelectorExists('#profile_firstName[value="Alex"]');
        $this->assertSelectorExists('#profile_phone[value="06 12 34 56 78"]');
    }

    public function testInvalidProfileRetainsValuesAndDisplaysErrors(): void
    {
        $this->loginAsNewUser();
        $crawler = $this->client->request('GET', '/profil');
        $form = $crawler->selectButton('Enregistrer')->form([
            'profile[firstName]' => '', 'profile[lastName]' => 'Review',
            'profile[address]' => '1 rue de test', 'profile[zipCode]' => 'abc',
            'profile[city]' => 'Nice', 'profile[phone]' => '',
        ]);
        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#firstName-error', 'Veuillez saisir votre prénom');
        $this->assertSelectorExists('#profile_firstName.is-invalid');
        $this->assertSelectorExists('#profile_firstName[aria-invalid="true"]');
        $this->assertSelectorTextContains('#zipCode-error', 'Veuillez saisir un code postal valide');
        $this->assertSelectorExists('#profile_lastName[value="Review"]');
        $this->assertSelectorExists('#profile-errors:not([hidden])[tabindex="-1"]');
        $this->assertSelectorTextContains('#profile-errors a[href="#profile_firstName"]', 'Veuillez saisir votre prénom');
        $this->assertSelectorTextContains('#profile-errors a[href="#profile_zipCode"]', 'Veuillez saisir un code postal valide');
        $this->assertSelectorExists('form[data-profile-unsaved="true"]');
        $this->assertSelectorExists('[data-profile-status]:not([hidden])');
        $this->assertPageTitleContains('Erreur :');
    }

    public function testProfileLoadsWithoutCallingHistoryApi(): void
    {
        $this->loginAsNewUser();
        $api = $this->createMock(HelloAssoApiService::class);
        $api->expects($this->never())->method('makeApiCall');
        static::getContainer()->set(HelloAssoApiService::class, $api);

        $this->client->request('GET', '/profil');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#profile-errors[hidden]');
        $this->assertSelectorExists('form[data-profile-unsaved="false"]');
        $this->assertSelectorExists('[data-profile-status][hidden]');
    }

    /** @dataProvider nameFieldProvider */
    public function testClearingASavedNameShowsValidationAndDoesNotPersist(string $field): void
    {
        $user = $this->loginAsNewUser();
        $payer = (new Payers())
            ->setEmail($user->getEmail())
            ->setFirstName('Alex')
            ->setLastName('Review')
            ->setAddress('1 rue de test')
            ->setZipCode('06000')
            ->setCity('Nice')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTime());
        $this->em->persist($payer);
        $this->em->flush();
        $id = $payer->getId();
        $crawler = $this->client->request('GET', '/profil');
        $form = $crawler->selectButton('Enregistrer')->form(['profile['.$field.']' => '']);

        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#profile-errors:not([hidden]) a[href="#profile_'.$field.'"]');
        $this->assertSelectorExists('#profile_city[value="Nice"]');
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();
        $savedPayer = $em->find(Payers::class, $id);
        $this->assertSame('Alex', $savedPayer->getFirstName());
        $this->assertSame('Review', $savedPayer->getLastName());
    }

    /** @return iterable<string, array{string}> */
    public static function nameFieldProvider(): iterable
    {
        yield 'first name' => ['firstName'];
        yield 'last name' => ['lastName'];
    }

    public function testErrorSummaryIncludesFormLevelErrorsWithoutAnInputLink(): void
    {
        $form = static::getContainer()->get('form.factory')
            ->createBuilder(FormType::class, null, ['csrf_protection' => false])
            ->getForm();
        $form->submit([]);
        $form->addError(new FormError('Votre session a expiré. Veuillez réessayer.'));
        $html = static::getContainer()->get(Environment::class)->render('profil/_error_summary.html.twig', [
            'form' => $form->createView(),
        ]);
        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('#profile-errors:not([hidden])'));
        $this->assertSame('Votre session a expiré. Veuillez réessayer.', $crawler->filter('[data-form-error]')->text());
        $this->assertCount(0, $crawler->filter('a'));
    }

    /** @dataProvider historyFailureProvider */
    public function testHistoryFailureOffersRetryOnTheSamePage(string $section, mixed $failure): void
    {
        $this->loginAsNewUser();
        $api = $this->createMock(HelloAssoApiService::class);
        $call = $api->expects($this->once())->method('makeApiCall');
        if ($failure instanceof \Throwable) {
            $call->willThrowException($failure);
        } else {
            $call->willReturn($failure);
        }
        static::getContainer()->set(HelloAssoApiService::class, $api);
        $url = '/profil/'.$section.'/2';

        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.account-history-error', 'Nous n\'avons pas pu charger votre historique');
        $this->assertSelectorTextContains('.account-history-error a[href="'.$url.'"]', 'Réessayer');
        $this->assertSelectorNotExists('.account-list');
        $this->assertSelectorNotExists('.pagination');
        $this->assertSelectorTextNotContains('#profile-content', 'à afficher pour le moment');
        $this->assertSelectorTextNotContains('#profile-content', 'Internal API failure');
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function historyFailureProvider(): iterable
    {
        foreach (['orders', 'payments'] as $section) {
            yield $section.' false response' => [$section, false];
            yield $section.' exception' => [$section, new \RuntimeException('Internal API failure')];
            yield $section.' malformed response' => [$section, ['data' => 'invalid']];
        }
    }

    /** @dataProvider historySectionProvider */
    public function testHistoryRetryCanRecoverToAGenuinelyEmptyHistory(string $section): void
    {
        $this->loginAsNewUser();
        $this->client->disableReboot();
        $api = $this->createMock(HelloAssoApiService::class);
        $api->expects($this->exactly(2))->method('makeApiCall')->willReturnOnConsecutiveCalls(false, ['data' => []]);
        static::getContainer()->set(HelloAssoApiService::class, $api);

        $crawler = $this->client->request('GET', '/profil/'.$section.'/2');
        $this->assertSelectorExists('.account-history-error');
        $this->client->click($crawler->selectLink('Réessayer')->link());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.account-history-error');
        $this->assertSelectorTextContains('.account-empty', 'à afficher pour le moment');
    }
}
