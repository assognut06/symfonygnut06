<?php

namespace App\Tests\Functional;

use App\Entity\Tih;
use App\Entity\TihApplicationEvent;

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
        $this->assertSelectorNotExists('#tih-history');
    }

    public function testTihSpaceShowsProfileForTihUser(): void
    {
        $tihUser = $this->createTihUser();
        $this->loginAs($tihUser);

        $crawler = $this->client->request('GET', '/espace-tih');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#tih-history', 'Aucun historique disponible pour le moment.');
    }

    public function testTihSpaceShowsOnlyOwnApplicationHistory(): void
    {
        $admin = $this->createAdmin('history-admin@test.com');
        $candidate = $this->createTihUser('history-candidate@test.com');
        $otherCandidate = $this->createTihUser('history-other@test.com');

        $events = [
            new TihApplicationEvent(
                $candidate->getTih(),
                TihApplicationEvent::STATUS_PENDING,
                $candidate,
                source: TihApplicationEvent::SOURCE_INITIAL_SUBMISSION,
            ),
            new TihApplicationEvent(
                $candidate->getTih(),
                TihApplicationEvent::STATUS_REFUSED,
                $admin,
                'Document à corriger.',
                TihApplicationEvent::SOURCE_ADMIN_DECISION,
            ),
            new TihApplicationEvent(
                $candidate->getTih(),
                TihApplicationEvent::STATUS_APPROVED,
                $admin,
                source: TihApplicationEvent::SOURCE_ADMIN_DECISION,
            ),
            new TihApplicationEvent(
                $otherCandidate->getTih(),
                TihApplicationEvent::STATUS_REFUSED,
                $admin,
                'Motif privé de l’autre candidat.',
                TihApplicationEvent::SOURCE_ADMIN_DECISION,
            ),
        ];
        foreach ($events as $event) {
            $event->getTih()->addApplicationEvent($event);
            $this->em->persist($event);
        }
        $this->em->flush();

        $this->loginAs($candidate);
        $this->client->request('GET', '/espace-tih');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#tih-history-title', 'Historique de la candidature');
        $this->assertCount(3, $this->client->getCrawler()->filter('#tih-history tbody tr'));
        $this->assertSelectorTextContains('#tih-history', 'En attente');
        $this->assertSelectorTextContains('#tih-history', 'Refusé');
        $this->assertSelectorTextContains('#tih-history', 'Validé');
        $this->assertSelectorTextContains('#tih-history', 'Document à corriger.');
        $this->assertSelectorTextContains('#tih-history', 'Vous');
        $this->assertSelectorTextContains('#tih-history', 'Équipe GNUT 06');
        $this->assertSelectorTextNotContains('#tih-history', 'Motif privé de l’autre candidat.');
        $this->assertSelectorTextNotContains('#tih-history', 'history-admin@test.com');
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
        $this->assertSelectorTextContains('h1#profile-title', 'Votre profil');
        $this->assertSelectorNotExists('.alert-warning');
        $this->assertSelectorNotExists('#tih-rejection-message');
        $this->assertSelectorTextNotContains('body', 'Votre attestation doit être renouvelée.');

        $this->loginAs($candidate);
        $this->client->request('GET', '/profil');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-warning[role="alert"]', 'Une information concernant votre profil TIH est disponible.');
        $this->assertSelectorExists('.alert-warning a[href="#tih-rejection-message"][aria-label="Consulter le message concernant mon profil TIH"]');
        $this->assertSelectorTextContains('#tih-rejection-message', 'Votre attestation doit être renouvelée.');
    }

    public function testProfileHidesHistoricalRefusalWhenApplicationIsPendingOrApproved(): void
    {
        $admin = $this->createAdmin('historical-refusal-admin@test.com');
        $candidate = $this->createTihUser('historical-refusal-candidate@test.com');
        $decision = new TihApplicationEvent(
            $candidate->getTih(),
            TihApplicationEvent::STATUS_REFUSED,
            $admin,
            'Ancien motif de refus.',
            TihApplicationEvent::SOURCE_ADMIN_DECISION,
        );
        $candidate->getTih()->addApplicationEvent($decision)->setApplicationStatus(Tih::STATUS_PENDING);
        $this->em->persist($decision);
        $this->em->flush();
        $this->loginAs($candidate);

        foreach ([Tih::STATUS_PENDING, Tih::STATUS_APPROVED] as $status) {
            $candidate->getTih()->setApplicationStatus($status);
            $this->em->flush();
            $this->client->request('GET', '/profil');

            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('h1#profile-title', 'Votre profil');
            $this->assertSelectorNotExists('.alert-warning');
            $this->assertSelectorNotExists('#tih-rejection-message');
        }
    }
}
