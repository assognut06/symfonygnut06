<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Competence;
use App\Entity\Tih;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class TihTest extends TestCase
{
    public function testNewTihHasDefaults(): void
    {
        $tih = new Tih();

        $this->assertNull($tih->getId());
        $this->assertFalse($tih->isValidate());
        $this->assertCount(0, $tih->getCompetences());
    }

    public function testUserRelation(): void
    {
        $tih = new Tih();
        $user = new User();

        $tih->setUser($user);
        $this->assertSame($user, $tih->getUser());
    }

    public function testPersonalInfoGettersSetters(): void
    {
        $tih = new Tih();

        $tih->setTitle('M.');
        $tih->setFirstName('Jean');
        $tih->setLastName('Dupont');
        $tih->setPhone('0612345678');
        $tih->setProfessionalEmail('jean@pro.com');

        $this->assertEquals('M.', $tih->getTitle());
        $this->assertEquals('Jean', $tih->getFirstName());
        $this->assertEquals('Dupont', $tih->getLastName());
        $this->assertEquals('0612345678', $tih->getPhone());
        $this->assertEquals('jean@pro.com', $tih->getProfessionalEmail());
    }

    public function testLocationGettersSetters(): void
    {
        $tih = new Tih();

        $tih->setAddress('1 rue de la Paix');
        $tih->setPostalCode('06000');
        $tih->setCity('Nice');
        $tih->setRegion('PACA');
        $tih->setDepartement('Alpes-Maritimes');

        $this->assertEquals('1 rue de la Paix', $tih->getAddress());
        $this->assertEquals('06000', $tih->getPostalCode());
        $this->assertEquals('Nice', $tih->getCity());
        $this->assertEquals('PACA', $tih->getRegion());
        $this->assertEquals('Alpes-Maritimes', $tih->getDepartement());
    }

    public function testRateGettersSetters(): void
    {
        $tih = new Tih();

        $tih->setRate('350.00');
        $tih->setRateType('daily');

        $this->assertEquals('350.00', $tih->getRate());
        $this->assertEquals('daily', $tih->getRateType());
    }

    public function testCompetenceCollection(): void
    {
        $tih = new Tih();
        $comp1 = new Competence();
        $comp2 = new Competence();

        $tih->addCompetence($comp1);
        $tih->addCompetence($comp2);

        $this->assertCount(2, $tih->getCompetences());
        $this->assertTrue($tih->getCompetences()->contains($comp1));

        $tih->addCompetence($comp1);
        $this->assertCount(2, $tih->getCompetences(), 'Adding duplicate should not increase count');

        $tih->removeCompetence($comp1);
        $this->assertCount(1, $tih->getCompetences());
    }

    public function testValidationFlow(): void
    {
        $tih = new Tih();

        $this->assertFalse($tih->isValidate());
        $this->assertNull($tih->getValidationMessage());

        $tih->setIsValidate(true);
        $this->assertTrue($tih->isValidate());

        $tih->setIsValidate(false);
        $tih->setValidationMessage('Documents manquants');
        $this->assertFalse($tih->isValidate());
        $this->assertEquals('Documents manquants', $tih->getValidationMessage());
    }

    public function testFileFields(): void
    {
        $tih = new Tih();

        $tih->setCv('cv-123.pdf');
        $tih->setAttestationTih('attest-456.pdf');
        $tih->setPhoto('photo-789.jpg');
        $tih->setSiret('12345678901234');

        $this->assertEquals('cv-123.pdf', $tih->getCv());
        $this->assertEquals('attest-456.pdf', $tih->getAttestationTih());
        $this->assertEquals('photo-789.jpg', $tih->getPhoto());
        $this->assertEquals('12345678901234', $tih->getSiret());
    }

    public function testPrePersistSetsTimestamps(): void
    {
        $tih = new Tih();
        $tih->onPrePersist();

        $this->assertNotNull($tih->getCreatedAt());
        $this->assertNotNull($tih->getUpdatedAt());
    }

    public function testSettersReturnSelf(): void
    {
        $tih = new Tih();

        $this->assertSame($tih, $tih->setTitle('M.'));
        $this->assertSame($tih, $tih->setFirstName('Jean'));
        $this->assertSame($tih, $tih->setLastName('Dupont'));
        $this->assertSame($tih, $tih->setCity('Nice'));
        $this->assertSame($tih, $tih->setRate('100'));
        $this->assertSame($tih, $tih->setIsValidate(true));
    }
}
