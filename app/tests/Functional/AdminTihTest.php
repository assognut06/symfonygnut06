<?php

namespace App\Tests\Functional;

use App\Entity\Tih;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for TIH administration (list, search, validate, refuse, delete).
 */
class AdminTihTest extends WebTestCase
{
    /** @var string[] */
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $filePath) {
            if (is_file($filePath)) {
                unlink($filePath);
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

        for ($i = 0; $i < 11; $i++) {
            $this->createTihUser(sprintf('paginated-tih-%d@test.com', $i));
        }

        $this->client->request('GET', '/admin/tih/2');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexPaginationCapsRequestedPageToLastAvailablePage(): void
    {
        $this->loginAsAdmin();
        $this->createTihUser('single-page-tih@test.com');

        $this->client->request('GET', '/admin/tih/999');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.pagination .page-item.active .page-link');
        $this->assertSelectorTextSame('.pagination .page-item.active .page-link', '1');
    }

    public function testValidateRejectsInvalidCsrf(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();
        $tih->setIsValidate(false);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/validate/' . $tih->getId(), [
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

        $this->client->request('POST', '/admin/tih/validate/' . $tih->getId(), [
            '_token' => $this->getAdminTihCsrfToken('validate', $tih->getId()),
        ]);

        $this->assertResponseRedirects('/admin/tih');
        $updated = $this->em->getRepository(Tih::class)->find($tih->getId());
        $this->assertTrue($updated->isValidate());
        $this->assertNull($updated->getValidationMessage());
    }

    public function testRefuseRejectsTihWithCustomMessage(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();
        $tih->setIsValidate(true);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/refuse/' . $tih->getId(), [
            '_token' => $this->getAdminTihCsrfToken('refuse', $tih->getId()),
            'validation_message' => 'Documents manquants',
        ]);

        $this->assertResponseRedirects('/admin/tih');
        $updated = $this->em->getRepository(Tih::class)->find($tih->getId());
        $this->assertFalse($updated->isValidate());
        $this->assertSame('Documents manquants', $updated->getValidationMessage());
    }

    public function testRefuseUsesDefaultMessageWhenEmpty(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();
        $tih->setIsValidate(true);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/refuse/' . $tih->getId(), [
            '_token' => $this->getAdminTihCsrfToken('refuse', $tih->getId()),
            'validation_message' => '   ',
        ]);

        $this->assertResponseRedirects('/admin/tih');
        $updated = $this->em->getRepository(Tih::class)->find($tih->getId());
        $this->assertFalse($updated->isValidate());
        $this->assertSame('Vos informations ne sont pas correctes.', $updated->getValidationMessage());
    }

    public function testRefuseRequiresCSRF(): void
    {
        $this->loginAsAdmin();
        $tihUser = $this->createTihUser();
        $tih = $tihUser->getTih();
        $tih->setIsValidate(true);
        $this->em->flush();

        $this->client->request('POST', '/admin/tih/refuse/' . $tih->getId(), [
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

        $this->client->request('POST', '/admin/tih/delete/' . $tihId, [
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

        $this->client->request('POST', '/admin/tih/delete/' . $tih->getId(), [
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

        $this->client->request('POST', '/admin/tih/delete/' . $tih->getId(), [
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

        $this->client->request('POST', '/admin/tih/validate/' . $id, ['_token' => $token]);

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

        $this->client->request('POST', '/admin/tih/refuse/' . $id, ['_token' => $token]);

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

        $this->client->request('POST', '/admin/tih/delete/' . $id, [
            '_method' => 'DELETE',
            '_token' => $token,
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDownloadCvReturnsInlineFileFromConfiguredDirectory(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'cv-configured@test.com']);
        $fileName = sprintf('cv-configured-%s.pdf', uniqid('', true));
        $this->createUploadedFile('var/tihcv', $fileName, 'cv-content');
        $tih->setCv($fileName);
        $this->em->flush();

        $this->client->request('GET', '/admin/tih/' . $tih->getId() . '/cv');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString(
            'inline; filename=' . $fileName,
            (string) $this->client->getResponse()->headers->get('Content-Disposition')
        );
    }

    public function testDownloadCvFallsBackToLegacyDirectory(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'cv-legacy@test.com']);
        $fileName = sprintf('cv-legacy-%s.pdf', uniqid('', true));
        $this->createUploadedFile('public/uploads/tihcv', $fileName, 'legacy-cv-content');
        $tih->setCv($fileName);
        $this->em->flush();

        $this->client->request('GET', '/admin/tih/' . $tih->getId() . '/cv');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString(
            'inline; filename=' . $fileName,
            (string) $this->client->getResponse()->headers->get('Content-Disposition')
        );
    }

    public function testDownloadCvReturns404WhenNoCvSet(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'cv-missing-field@test.com']);
        $tih->setCv(null);
        $this->em->flush();

        $this->client->request('GET', '/admin/tih/' . $tih->getId() . '/cv');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDownloadCvReturns404WhenStoredFileIsMissing(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'cv-file-missing@test.com']);
        $tih->setCv('missing-file.pdf');
        $this->em->flush();

        $this->client->request('GET', '/admin/tih/' . $tih->getId() . '/cv');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDownloadCvReturns404WhenTihMissing(): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/tih/999999/cv');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDownloadAttestationReturnsInlineFileFromConfiguredDirectory(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'att-configured@test.com']);
        $fileName = sprintf('att-configured-%s.pdf', uniqid('', true));
        $this->createUploadedFile('var/tihattest', $fileName, 'att-content');
        $tih->setAttestationTih($fileName);
        $this->em->flush();

        $this->client->request('GET', '/admin/tih/' . $tih->getId() . '/attestation');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString(
            'inline; filename=' . $fileName,
            (string) $this->client->getResponse()->headers->get('Content-Disposition')
        );
    }

    public function testDownloadAttestationFallsBackToLegacyDirectory(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'att-legacy@test.com']);
        $fileName = sprintf('att-legacy-%s.pdf', uniqid('', true));
        $this->createUploadedFile('public/uploads/tihattest', $fileName, 'legacy-att-content');
        $tih->setAttestationTih($fileName);
        $this->em->flush();

        $this->client->request('GET', '/admin/tih/' . $tih->getId() . '/attestation');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString(
            'inline; filename=' . $fileName,
            (string) $this->client->getResponse()->headers->get('Content-Disposition')
        );
    }

    public function testDownloadAttestationReturns404WhenNoFileSet(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'att-missing-field@test.com']);
        $tih->setAttestationTih(null);
        $this->em->flush();

        $this->client->request('GET', '/admin/tih/' . $tih->getId() . '/attestation');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDownloadAttestationReturns404WhenStoredFileIsMissing(): void
    {
        $this->loginAsAdmin();
        $tih = $this->createSearchableTih(['email' => 'att-file-missing@test.com']);
        $tih->setAttestationTih('missing-attestation.pdf');
        $this->em->flush();

        $this->client->request('GET', '/admin/tih/' . $tih->getId() . '/attestation');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDownloadAttestationReturns404WhenTihMissing(): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/tih/999999/attestation');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function createUploadedFile(string $relativeDirectory, string $fileName, string $content): void
    {
        $projectDir = (string) static::getContainer()->getParameter('kernel.project_dir');
        $directory = rtrim($projectDir, '/') . '/' . trim($relativeDirectory, '/');

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filePath = $directory . '/' . $fileName;
        file_put_contents($filePath, $content);
        $this->createdFiles[] = $filePath;
    }
}
