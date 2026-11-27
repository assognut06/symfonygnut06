<?php

namespace App\Tests\Functional;

use App\Entity\Tih;

class TihDemoTest extends WebTestCase
{
    public function testPublicDemoFormCreatesPendingDemoWithoutDocuments(): void
    {
        $crawler = $this->client->request('GET', '/tih/demo/ajouter');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-danger', 'Ne pas entrer de données personnelles réelles sur ce formulaire.');
        $this->assertSelectorNotExists('input[type="file"]');

        $form = $crawler->selectButton('Proposer ce profil fictif')->form([
            'tih[civilite]' => 'Autre',
            'tih[nom]' => 'Exemple',
            'tih[prenom]' => 'Camille',
            'tih[emailPro]' => 'camille.exemple@example.com',
            'tih[telephone]' => '0600000000',
            'tih[adresse]' => '10 rue Imaginaire',
            'tih[codePostal]' => '06000',
            'tih[ville]' => 'Ville fictive',
            'tih[region]' => 'Région fictive',
            'tih[departement]' => 'Département fictif',
            'tih[siret]' => '00000000000000',
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/tih/tih_search');
        $tih = $this->em->getRepository(Tih::class)->findOneBy(['professionalEmail' => 'camille.exemple@example.com']);
        self::assertInstanceOf(Tih::class, $tih);
        self::assertTrue($tih->isDemo());
        self::assertSame(Tih::STATUS_PENDING, $tih->getApplicationStatus());
        self::assertNull($tih->getPhoto());
        self::assertNull($tih->getCv());
        self::assertNull($tih->getAttestationTih());
    }

    public function testAdministratorCanApprovePendingDemo(): void
    {
        $tih = new Tih();
        $tih->setProfileKind(Tih::PROFILE_KIND_DEMO);
        $tih->setFirstName('Profil');
        $tih->setLastName('Fictif');
        $tih->setApplicationStatus(Tih::STATUS_PENDING);
        $this->em->persist($tih);
        $this->em->flush();

        $admin = $this->createAdmin('demo-admin@test.com');
        $this->loginAs($admin);
        $token = $this->generateCsrfToken('approve_demo_tih'.$tih->getId());

        $this->client->request('POST', '/admin/tih-demo/'.$tih->getId().'/approve', ['_token' => $token]);

        $this->assertResponseRedirects('/admin/tih-demo');
        $approvedTih = $this->em->getRepository(Tih::class)->find($tih->getId());
        self::assertInstanceOf(Tih::class, $approvedTih);
        self::assertSame(Tih::STATUS_APPROVED, $approvedTih->getApplicationStatus());
        self::assertTrue($approvedTih->isValidate());
    }

    public function testPublicAndIntranetSearchesOnlyShowDemoProfiles(): void
    {
        $demo = $this->createSearchableTih([
            'email' => 'demo-search@test.com',
            'firstName' => 'Démo',
            'lastName' => 'Publique',
            'profileKind' => Tih::PROFILE_KIND_DEMO,
        ]);
        $real = $this->createSearchableTih([
            'email' => 'real-search@test.com',
            'firstName' => 'Profil',
            'lastName' => 'Réel',
            'profileKind' => Tih::PROFILE_KIND_REAL,
        ]);

        $this->client->request('GET', '/tih/tih_search');
        $this->assertTihGridContains('Démo Publique');
        $this->assertTihGridNotContains('Profil Réel');

        $this->loginAs($this->createAdmin('real-search-admin@test.com'));
        $this->client->request('GET', '/intranet/tih/tih_search');
        $this->assertResponseIsSuccessful();
        $this->assertTihGridContains('Démo Publique');
        $this->assertTihGridNotContains('Profil Réel');

        $this->client->request('GET', '/tih/tih/'.$real->getId());
        $this->assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/intranet/tih/tih/'.$real->getId());
        $this->assertResponseStatusCodeSame(404);
    }
}
