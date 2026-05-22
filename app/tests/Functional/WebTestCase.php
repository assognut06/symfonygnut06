<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Tih;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class WebTestCase extends BaseWebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function createUser(
        string $email = 'user@test.com',
        string $password = 'Test1234!',
        array $roles = [],
        bool $verified = true
    ): User {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setRoles($roles);
        $user->setVerified($verified);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function createAdmin(
        string $email = 'admin@test.com',
        string $password = 'Admin1234!'
    ): User {
        return $this->createUser($email, $password, ['ROLE_ADMIN']);
    }

    protected function createTihUser(
        string $email = 'tih@test.com',
        string $password = 'Tih1234!'
    ): User {
        $user = $this->createUser($email, $password, ['ROLE_TIH']);

        $tih = new Tih();
        $tih->setUser($user);
        $tih->setFirstName('Jean');
        $tih->setLastName('Dupont');
        $tih->setCity('Nice');
        $tih->setRegion('PACA');
        $tih->setIsValidate(true);

        $user->setTih($tih);

        $this->em->persist($tih);
        $this->em->flush();

        return $user;
    }

    protected function loginAs(User $user): void
    {
        $this->client->loginUser($user);
    }

    protected function loginAsNewUser(): User
    {
        $user = $this->createUser();
        $this->loginAs($user);
        return $user;
    }

    protected function loginAsAdmin(): User
    {
        $admin = $this->createAdmin();
        $this->loginAs($admin);
        return $admin;
    }

    protected function assertResponseRedirectsToLogin(): void
    {
        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('login', $location);
    }

    protected function getAdminTihCsrfToken(string $action, int $tihId): string
    {
        $this->client->request('GET', '/admin/tih');
        $crawler = $this->client->getCrawler();

        $selector = $action === 'refuse'
            ? sprintf('#refuse-form-%d input[name="_token"]', $tihId)
            : sprintf('form[action*="/admin/tih/%s/%d"] input[name="_token"]', $action, $tihId);

        return $crawler->filter($selector)->attr('value');
    }
}
