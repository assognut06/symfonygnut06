<?php

namespace App\Tests\Functional;

use App\Entity\Tih;
use App\Entity\TihApplicationEvent;
use App\Entity\User;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Functional tests for TIH administration (list, search, validate, refuse, delete).
 */
class AdminTihTest extends WebTestCase
{
    /** @var list<string> */
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->createdFiles = [];
        parent::tearDown();
    }

    public function testIndexListsTihProfiles(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser('listed-tih@test.com');
        $tih = $tihUser->getTih();

        $this->client->request('GET', '/admin/tih');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table#tih-table', (string) $tih->getId());
        $this->assertSelectorTextContains('table#tih-table', 'listed-tih@test.com');
    }

    public function testIndexSearchFiltersByEmail(): void
    {
        $this->loginAsAdmin();
        $this->createTihUser('alpha-tih@test.com');
        $this->createTihUser('beta-tih@test.com');

        $this->client->request('GET', '/admin/tih', ['q' => 'alpha-tih']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table#tih-table', 'alpha-tih@test.com');
        $this->assertSelectorTextNotContains('table#tih-table', 'beta-tih@test.com');
    }

    public function testIndexSearchFiltersByProfessionalEmail(): void
    {
        $this->loginAsAdmin();
        $this->createSearchableTih([
            'email' => 'account-pro@example.com',
            'professionalEmail' => 'contact-pro@example.com',
        ]);
        $this->createSearchableTih([
            'email' => 'other-pro@example.com',
            'professionalEmail' => 'other-contact@example.com',
        ]);

        $this->client->request('GET', '/admin/tih', ['q' => 'contact-pro']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table#tih-table', 'contact-pro@example.com');
        $this->assertSelectorTextNotContains('table#tih-table', 'other-contact@example.com');
    }

    public function testIndexSearchFiltersByFirstAndLastName(): void
    {
        $this->loginAsAdmin();
        $this->createSearchableTih([
            'email' => 'named-tih@example.com',
            'firstName' => 'Camille',
            'lastName' => 'Martin',
        ]);
        $this->createSearchableTih([
            'email' => 'unnamed-tih@example.com',
            'firstName' => 'Alex',
            'lastName' => 'Durand',
        ]);

        $this->client->request('GET', '/admin/tih', ['q' => 'camille']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table#tih-table', 'named-tih@example.com');
        $this->assertSelectorTextNotContains('table#tih-table', 'unnamed-tih@example.com');

        $this->client->request('GET', '/admin/tih', ['q' => 'martin']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table#tih-table', 'named-tih@example.com');
        $this->assertSelectorTextNotContains('table#tih-table', 'unnamed-tih@example.com');
    }

    public function testIndexPagination(): void
    {
        $this->loginAsAdmin();

        for ($i = 0; $i < 11; ++$i) {
            $this->createTihUser(sprintf('paginated-tih-%d@test.com', $i));
        }

        $this->client->request('GET', '/admin/tih/2');

        $this->assertResponseIsSuccessful();
    }

    public function testDownloadCvReturnsInlineFileFromConfiguredDirectory(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'cv-download@example.com']);
        $tih->setCv('admin-cv.pdf');
        $this->em->flush();

        $this->createStoredFile((string) static::getContainer()->getParameter('cv_tih_directory'), 'admin-cv.pdf', 'cv');

        $this->client->request('GET', '/admin/tih/'.$tih->getId().'/cv');

        $this->assertInlineDownloadResponse('admin-cv.pdf');
    }

    public function testDownloadCvFallsBackToLegacyDirectory(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'cv-legacy@example.com']);
        $tih->setCv('legacy-admin-cv.pdf');
        $this->em->flush();

        $projectDir = (string) static::getContainer()->getParameter('kernel.project_dir');
        $this->createStoredFile($projectDir.'/public/uploads/tihcv', 'legacy-admin-cv.pdf', 'legacy-cv');

        $this->client->request('GET', '/admin/tih/'.$tih->getId().'/cv');

        $this->assertInlineDownloadResponse('legacy-admin-cv.pdf');
    }

    public function testDownloadCvReturns404WhenStoredFileIsMissing(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'cv-missing-file@example.com']);
        $tih->setCv('missing-admin-cv.pdf');
        $this->em->flush();

        $this->client->request('GET', '/admin/tih/'.$tih->getId().'/cv');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDownloadCvReturns404WhenTihIsMissing(): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/tih/999999/cv');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDownloadAttestationReturnsInlineFileFromConfiguredDirectory(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'attestation-download@example.com']);
        $tih->setAttestationTih('admin-attestation.pdf');
        $this->em->flush();

        $this->createStoredFile((string) static::getContainer()->getParameter('attestation_tih_directory'), 'admin-attestation.pdf', 'attestation');

        $this->client->request('GET', '/admin/tih/'.$tih->getId().'/attestation');

        $this->assertInlineDownloadResponse('admin-attestation.pdf');
    }

    public function testDownloadAttestationReturns404WhenNoDocumentIsStored(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'attestation-missing@example.com']);

        $this->client->request('GET', '/admin/tih/'.$tih->getId().'/attestation');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testValidateRejectsInvalidCsrf(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();
        $tih->setIsValidate(false);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/validate/'.$tih->getId(), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseRedirects('/admin/tih');
        $updated = $this->em->getRepository(Tih::class)->find($tih->getId());
        $this->assertFalse($updated->isValidate());
    }

    public function testValidateApprovesTihProfile(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();
        $tih->setIsValidate(false);
        $tih->setValidationMessage('Profil incomplet');
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/validate/'.$tih->getId(), [
            '_token' => $this->getAdminTihCsrfToken('validate', $tih->getId()),
        ]);

        $this->assertResponseRedirects('/admin/tih');
        $updated = $this->em->getRepository(Tih::class)->find($tih->getId());
        $this->assertTrue($updated->isValidate());
        $this->assertNull($updated->getValidationMessage());
        $this->assertSame(TihApplicationEvent::STATUS_APPROVED, $updated->getApplicationEvents()->first()->getStatus());
    }

    public function testRefuseRejectsTihWithCustomMessage(): void
    {
        $admin = $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();
        $tih->setIsValidate(true);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/refuse/'.$tih->getId(), [
            '_token' => $this->getAdminTihCsrfToken('refuse', $tih->getId()),
            'rejection_reason' => 'Documents manquants',
        ]);

        $this->assertResponseRedirects('/admin/tih');
        $updated = $this->em->getRepository(Tih::class)->find($tih->getId());
        $this->assertFalse($updated->isValidate());
        $this->assertSame(Tih::STATUS_REFUSED, $updated->getApplicationStatus());
        $this->assertSame('Documents manquants', $updated->getValidationMessage());
        $decision = $updated->getLatestRefusalEvent();
        $this->assertNotNull($decision);
        $this->assertSame('Documents manquants', $decision->getReason());
        $this->assertSame($admin->getId(), $decision->getActor()?->getId());
        $this->assertSame(TihApplicationEvent::EMAIL_SENT, $decision->getEmailStatus());
        self::assertEmailCount(1);
        $email = self::getMailerMessage(0);
        self::assertNotNull($email);
        self::assertEmailAddressContains($email, 'to', $tihUser->getEmail());
        self::assertEmailHtmlBodyContains($email, 'Documents manquants');
    }

    public function testValidatedRefusedValidatedCycleKeepsCompleteHistory(): void
    {
        $admin = $this->loginAsAdmin();
        $candidate = $this->createTihUser('validated-refused-validated@test.com');
        $tih = $candidate->getTih();
        $tihId = $tih->getId();
        $initialApproval = new TihApplicationEvent(
            $tih,
            TihApplicationEvent::STATUS_APPROVED,
            $admin,
            source: TihApplicationEvent::SOURCE_ADMIN_DECISION,
        );
        $tih->addApplicationEvent($initialApproval);
        $this->em->persist($initialApproval);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/refuse/'.$tihId, [
            '_token' => $this->getAdminTihCsrfToken('refuse', $tihId),
            'rejection_reason' => 'Merci de remplacer l’attestation.',
        ]);

        $this->assertResponseRedirects('/admin/tih');
        self::assertEmailCount(1);

        $this->client->request('POST', '/admin/tih/validate/'.$tihId, [
            '_token' => $this->getAdminTihCsrfToken('validate', $tihId),
        ]);

        $this->assertResponseRedirects('/admin/tih');
        self::assertEmailCount(0);
        $this->em->clear();
        $tih = $this->em->getRepository(Tih::class)->find($tihId);
        $this->assertNotNull($tih);
        $this->assertSame(Tih::STATUS_APPROVED, $tih->getApplicationStatus());
        $this->assertTrue($tih->isValidate());

        $events = $this->em->getRepository(TihApplicationEvent::class)->findBy(['tih' => $tih], ['id' => 'ASC']);
        $this->assertSame(
            [Tih::STATUS_APPROVED, Tih::STATUS_REFUSED, Tih::STATUS_APPROVED],
            array_map(static fn (TihApplicationEvent $event): string => $event->getStatus(), $events),
        );
        $this->assertSame('Merci de remplacer l’attestation.', $events[1]->getReason());
        $this->assertSame($admin->getId(), $events[1]->getActor()?->getId());
    }

    public function testPendingRefusedCandidateUpdatePendingValidatedCycleKeepsCompleteHistory(): void
    {
        $admin = $this->loginAsAdmin();
        $adminId = $admin->getId();
        $candidate = $this->createTihUser('pending-refused-pending-validated@test.com');
        $tih = $candidate->getTih();
        $tihId = $tih->getId();
        $initialSubmission = new TihApplicationEvent(
            $tih,
            TihApplicationEvent::STATUS_PENDING,
            $candidate,
            source: TihApplicationEvent::SOURCE_INITIAL_SUBMISSION,
        );
        $tih->addApplicationEvent($initialSubmission)->setApplicationStatus(Tih::STATUS_PENDING);
        $this->em->persist($initialSubmission);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/refuse/'.$tihId, [
            '_token' => $this->getAdminTihCsrfToken('refuse', $tihId),
            'rejection_reason' => 'Un document complémentaire est nécessaire.',
        ]);
        $this->assertResponseRedirects('/admin/tih');
        self::assertEmailCount(1);

        $this->loginAs($candidate);
        $crawler = $this->client->request('GET', '/espace-tih?edit=1');
        $form = $crawler->selectButton('Enregistrer')->form([
            'tih[nom]' => 'Dupont',
            'tih[prenom]' => 'Jean',
            'tih[emailPro]' => 'jean.dupont-pro@test.com',
            'tih[telephone]' => '0612345678',
            'tih[codePostal]' => '06000',
            'tih[ville]' => 'Nice',
            'tih[region]' => 'Provence-Alpes-Côte d’Azur',
            'tih[departement]' => 'Alpes-Maritimes',
            'tih[siret]' => '12345678901234',
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/espace-tih');
        self::assertEmailCount(0);
        $this->em->clear();
        $tih = $this->em->getRepository(Tih::class)->find($tihId);
        $this->assertNotNull($tih);
        $this->assertSame(Tih::STATUS_PENDING, $tih->getApplicationStatus());
        $this->assertFalse($tih->isValidate());

        $admin = $this->em->getRepository(User::class)->find($adminId);
        $this->assertNotNull($admin);
        $this->loginAs($admin);
        $this->client->request('POST', '/admin/tih/validate/'.$tihId, [
            '_token' => $this->getAdminTihCsrfToken('validate', $tihId),
        ]);

        $this->assertResponseRedirects('/admin/tih');
        self::assertEmailCount(0);
        $this->em->clear();
        $tih = $this->em->getRepository(Tih::class)->find($tihId);
        $this->assertNotNull($tih);
        $this->assertSame(Tih::STATUS_APPROVED, $tih->getApplicationStatus());

        $events = $this->em->getRepository(TihApplicationEvent::class)->findBy(['tih' => $tih], ['id' => 'ASC']);
        $this->assertSame(
            [Tih::STATUS_PENDING, Tih::STATUS_REFUSED, Tih::STATUS_PENDING, Tih::STATUS_APPROVED],
            array_map(static fn (TihApplicationEvent $event): string => $event->getStatus(), $events),
        );
        $this->assertSame(TihApplicationEvent::SOURCE_PROFILE_UPDATE, $events[2]->getSource());
        $this->assertSame($candidate->getId(), $events[2]->getActor()?->getId());
        $this->assertSame('Un document complémentaire est nécessaire.', $events[1]->getReason());
    }

    public function testRefuseRejectsWhitespaceOnlyReasonWithoutChangingApplication(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();
        $tih->setIsValidate(true);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/refuse/'.$tih->getId(), [
            '_token' => $this->getAdminTihCsrfToken('refuse', $tih->getId()),
            'rejection_reason' => '   ',
        ]);

        $this->assertResponseRedirects('/admin/tih');
        $updated = $this->em->getRepository(Tih::class)->find($tih->getId());
        $this->assertTrue($updated->isValidate());
        $this->assertSame(Tih::STATUS_APPROVED, $updated->getApplicationStatus());
        $this->assertNull($updated->getLatestRefusalEvent());
        self::assertEmailCount(0);
    }

    public function testSecondRefusalSubmissionDoesNotSendAnotherEmail(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createTihUser('single-rejection@test.com')->getTih();
        $token = $this->getAdminTihCsrfToken('refuse', $tih->getId());

        $payload = ['_token' => $token, 'rejection_reason' => 'Merci de corriger le document.'];
        $this->client->request('POST', '/admin/tih/refuse/'.$tih->getId(), $payload);
        self::assertEmailCount(1);

        $this->client->request('POST', '/admin/tih/refuse/'.$tih->getId(), $payload);

        self::assertEmailCount(0);
        $this->assertCount(1, $this->em->getRepository(TihApplicationEvent::class)->findBy([
            'tih' => $tih,
            'status' => TihApplicationEvent::STATUS_REFUSED,
        ]));
    }

    public function testRetryFailedRejectionEmailSendsOnceWithoutCreatingAnotherEvent(): void
    {
        $admin = $this->loginAsAdmin();
        $candidate = $this->createTihUser('retry-rejection-email@test.com');
        $tih = $candidate->getTih();
        $tihId = $tih->getId();
        $event = new TihApplicationEvent(
            $tih,
            TihApplicationEvent::STATUS_REFUSED,
            $admin,
            'Merci de fournir une attestation à jour.',
            TihApplicationEvent::SOURCE_ADMIN_DECISION,
        );
        $event->markEmailFailed('Erreur SMTP temporaire');
        $tih->addApplicationEvent($event)->setApplicationStatus(Tih::STATUS_REFUSED);
        $this->em->persist($event);
        $this->em->flush();

        $eventId = $event->getId();
        $token = $this->generateCsrfToken('retry_tih_rejection_email'.$eventId);
        $this->client->request('POST', '/admin/tih/rejection/'.$eventId.'/retry-email', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/admin/tih');
        self::assertEmailCount(1);
        $email = self::getMailerMessage(0);
        self::assertNotNull($email);
        self::assertEmailAddressContains($email, 'to', $candidate->getEmail());
        self::assertEmailHtmlBodyContains($email, 'Merci de fournir une attestation à jour.');

        $this->em->clear();
        $updatedEvent = $this->em->getRepository(TihApplicationEvent::class)->find($eventId);
        $this->assertNotNull($updatedEvent);
        $this->assertSame(TihApplicationEvent::EMAIL_SENT, $updatedEvent->getEmailStatus());
        $this->assertNotNull($updatedEvent->getEmailSentAt());
        $this->assertNull($updatedEvent->getEmailError());
        $this->assertCount(1, $this->em->getRepository(TihApplicationEvent::class)->findBy([
            'tih' => $updatedEvent->getTih(),
            'status' => TihApplicationEvent::STATUS_REFUSED,
        ]));

        $this->client->request('POST', '/admin/tih/rejection/'.$eventId.'/retry-email', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/admin/tih');
        self::assertEmailCount(0);
        $this->em->clear();
        $tih = $this->em->getRepository(Tih::class)->find($tihId);
        $this->assertNotNull($tih);
        $this->assertCount(1, $this->em->getRepository(TihApplicationEvent::class)->findBy([
            'tih' => $tih,
            'status' => TihApplicationEvent::STATUS_REFUSED,
        ]));
    }

    public function testRefuseRequiresCSRF(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();
        $tih->setIsValidate(true);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/refuse/'.$tih->getId(), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseRedirects('/admin/tih');
        $updated = $this->em->getRepository(Tih::class)->find($tih->getId());
        $this->assertTrue($updated->isValidate());
    }

    public function testDeleteRemovesTihProfile(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser('delete-me@test.com');
        $tih = $tihUser->getTih();
        $tihId = $tih->getId();

        $this->client->request('POST', '/admin/tih/delete/'.$tihId, [
            '_method' => 'DELETE',
            '_token' => $this->getAdminTihCsrfToken('delete', $tihId),
        ]);

        $this->assertResponseRedirects('/admin/tih');
        $this->assertNull($this->em->getRepository(Tih::class)->find($tihId));
    }

    public function testDeleteRejectsWrongMethod(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();

        $this->client->request('POST', '/admin/tih/delete/'.$tih->getId(), [
            '_method' => 'POST',
            '_token' => $this->getAdminTihCsrfToken('delete', $tih->getId()),
        ]);

        $this->assertResponseRedirects('/admin/tih');
        $this->assertNotNull($this->em->getRepository(Tih::class)->find($tih->getId()));
    }

    public function testDeleteRequiresCSRF(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();

        $this->client->request('POST', '/admin/tih/delete/'.$tih->getId(), [
            '_method' => 'DELETE',
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseRedirects('/admin/tih');
        $this->assertNotNull($this->em->getRepository(Tih::class)->find($tih->getId()));
    }

    public function testValidateReturns404WhenTihMissing(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();
        $id = $tih->getId();
        $token = $this->getAdminTihCsrfToken('validate', $id);

        $this->em->remove($tih);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/validate/'.$id, ['_token' => $token]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRefuseReturns404WhenTihMissing(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();
        $id = $tih->getId();
        $token = $this->getAdminTihCsrfToken('refuse', $id);

        $this->em->remove($tih);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/refuse/'.$id, ['_token' => $token]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteReturns404WhenTihMissing(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();
        $id = $tih->getId();
        $token = $this->getAdminTihCsrfToken('delete', $id);

        $this->em->remove($tih);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/delete/'.$id, [
            '_method' => 'DELETE',
            '_token' => $token,
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    private function createStoredFile(string $directory, string $filename, string $contents): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $path = rtrim($directory, '/').'/'.$filename;
        file_put_contents($path, $contents);
        $this->createdFiles[] = $path;
    }

    private function assertInlineDownloadResponse(string $filename): void
    {
        $this->assertResponseIsSuccessful();
        self::assertInstanceOf(BinaryFileResponse::class, $this->client->getResponse());
        self::assertStringContainsString('inline', (string) $this->client->getResponse()->headers->get('content-disposition'));
        self::assertStringContainsString($filename, (string) $this->client->getResponse()->headers->get('content-disposition'));
    }
}
