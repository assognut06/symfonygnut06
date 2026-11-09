<?php

namespace App\Tests\Functional;

use App\Entity\Tih;
use App\Entity\TihApplicationEvent;

/**
 * Tests the TIH (espace-tih) profile management for logged-in users.
 */
class TihProfileTest extends WebTestCase
{
    public function testRemovedProfileRequiresExplicitNewApplication(): void
    {
        $user = $this->createTihUser();
        $user->getTih()->setApplicationStatus(Tih::STATUS_REMOVED);
        $user->setRoles(['ROLE_USER']);
        $this->em->flush();
        $this->loginAs($user);

        $this->client->request('GET', '/espace-tih?edit=1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-secondary', 'Votre profil TIH est supprimé');
        $this->assertSelectorNotExists('form[name="tih"]');
        $this->assertSelectorExists('a[href="/espace-tih?reapply=1"]');

        $this->client->request('GET', '/espace-tih?reapply=1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="tih"]');
        $this->assertSelectorTextContains('form[name="tih"] button[type="submit"]', 'Soumettre une nouvelle demande');
        $this->em->clear();
        $this->assertTrue($this->em->getRepository(Tih::class)->find($user->getTih()->getId())->isRemoved());
    }

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

    public function testProfileShowsOnlyLoggedInCandidatesRejectionReason(): void
    {
        $admin = $this->createAdmin('rejection-admin@test.com');
        $candidate = $this->createTihUser('rejected-candidate@test.com');
        $otherCandidate = $this->createTihUser('other-candidate@test.com');
        $decision = new TihApplicationEvent(
            $candidate->getTih(),
            TihApplicationEvent::STATUS_REFUSED,
            $admin,
            'Votre attestation doit être renouvelée.',
            TihApplicationEvent::SOURCE_ADMIN_DECISION,
        );
        $candidate->getTih()->addApplicationEvent($decision)->setApplicationStatus(Tih::STATUS_REFUSED);
        $this->em->persist($decision);
        $this->em->flush();

        $this->loginAs($otherCandidate);
        $this->client->request('GET', '/profil');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('#tih-rejection-message');
        $this->assertSelectorTextNotContains('body', 'Votre attestation doit être renouvelée.');

        $this->loginAs($candidate);
        $this->client->request('GET', '/profil');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-warning', 'Vous avez un message concernant votre candidature TIH.');
        $this->assertSelectorTextContains('#tih-rejection-message', 'Votre attestation doit être renouvelée.');
    }
}
