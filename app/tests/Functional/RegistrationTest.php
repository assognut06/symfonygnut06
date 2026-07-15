<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Scenario-based tests for user registration.
 */
class RegistrationTest extends WebTestCase
{
    public function testRegistrationPageLoads(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHtmlContainsForm();
    }

    public function testSuccessfulRegistrationCreatesUserAndRequiresEmailVerification(): void
    {
        $this->submitRegistrationForm([
            'email' => 'newuser@test.com',
            'plainPassword' => 'V3ryStr0ngP@ss!',
            'confirmPassword' => 'V3ryStr0ngP@ss!',
            'agreeTerms' => true,
        ]);

        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('login', $location);

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'newuser@test.com']);
        $this->assertNotNull($user, 'User should be persisted after registration');
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertFalse($user->isVerified(), 'New users must verify their email before logging in');

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue(
            $hasher->isPasswordValid($user, 'V3ryStr0ngP@ss!'),
            'Password should be hashed and verifiable'
        );

        $this->client->request('GET', '/profil');
        $this->assertResponseRedirects();
        $profilRedirect = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('login', $profilRedirect);
    }

    public function testRegistrationWithTihCheckboxCreatesLinkedProfile(): void
    {
        $this->submitRegistrationForm([
            'email' => 'tih-new@test.com',
            'plainPassword' => 'V3ryStr0ngP@ss!',
            'confirmPassword' => 'V3ryStr0ngP@ss!',
            'agreeTerms' => true,
            'isTih' => true,
        ]);

        $this->assertResponseRedirects();

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'tih-new@test.com']);
        $this->assertNotNull($user);
        $this->assertNotNull($user->getTih(), 'TIH checkbox should create a linked Tih profile');
        $this->assertFalse($user->getTih()->isValidate(), 'New TIH profile should start unvalidated');
    }

    public function testRegistrationWithEmptyFormShowsValidationErrors(): void
    {
        $this->submitRegistrationForm([
            'email' => '',
            'plainPassword' => '',
            'confirmPassword' => '',
            'agreeTerms' => false,
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', 'Veuillez entrer votre email');
        $this->assertSame(0, $this->countUsers());
    }

    public function testRegistrationWithMismatchedPasswordsShowsError(): void
    {
        $this->submitRegistrationForm([
            'email' => 'mismatch@test.com',
            'plainPassword' => 'V3ryStr0ngP@ss!',
            'confirmPassword' => 'DifferentP@ssw0rd!',
            'agreeTerms' => true,
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', 'Les mots de passe doivent correspondre');
        $this->assertNull($this->em->getRepository(User::class)->findOneBy(['email' => 'mismatch@test.com']));
    }

    public function testRegistrationWithDuplicateEmailDoesNotCreateSecondUser(): void
    {
        $this->createUser('existing@test.com', 'ExistingPass1!');

        $this->submitRegistrationForm([
            'email' => 'existing@test.com',
            'plainPassword' => 'NewStr0ngP@ss!',
            'confirmPassword' => 'NewStr0ngP@ss!',
            'agreeTerms' => true,
        ]);

        $this->assertGreaterThanOrEqual(
            400,
            $this->client->getResponse()->getStatusCode(),
            'Duplicate email must not complete registration successfully'
        );
        $this->assertSame(1, $this->countUsers());
        $this->assertFalse(
            $this->client->getResponse()->isRedirection(),
            'Duplicate email must not redirect as if registration succeeded'
        );
    }

    private function countUsers(): int
    {
        return count($this->em->getRepository(User::class)->findAll());
    }
}
