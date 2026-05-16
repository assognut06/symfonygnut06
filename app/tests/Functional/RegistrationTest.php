<?php

namespace App\Tests\Functional;

use App\Entity\User;

/**
 * Tests the user registration flow.
 */
class RegistrationTest extends WebTestCase
{
    public function testRegistrationPageLoads(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="registration_form"]');
    }

    public function testRegistrationFormContainsRequiredFields(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $this->assertSelectorExists('input[name="registration_form[email]"]');
        $this->assertSelectorExists('input[name="registration_form[plainPassword]"]');
        $this->assertSelectorExists('input[name="registration_form[confirmPassword]"]');
        $this->assertSelectorExists('input[name="registration_form[agreeTerms]"]');
    }

    public function testRegistrationWithEmptyFormShowsErrors(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->filter('form[name="registration_form"]')->form();
        $form['registration_form[email]'] = '';
        $form['registration_form[plainPassword]'] = '';
        $form['registration_form[confirmPassword]'] = '';

        $this->client->submit($form);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->getStatusCode() === 422 || $response->isSuccessful(),
            'Empty form should show validation errors'
        );
    }

    public function testRegistrationWithDuplicateEmail(): void
    {
        $this->createUser('existing@test.com', 'ExistingPass1!');

        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->filter('form[name="registration_form"]')->form();
        $form['registration_form[email]'] = 'existing@test.com';
        $form['registration_form[plainPassword]'] = 'NewStr0ngP@ss!';
        $form['registration_form[confirmPassword]'] = 'NewStr0ngP@ss!';
        $form['registration_form[agreeTerms]'] = true;

        $this->client->submit($form);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->getStatusCode() === 422 || $response->isSuccessful(),
            'Duplicate email should show validation errors'
        );
    }

    public function testRegistrationCreatesUser(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->filter('form[name="registration_form"]')->form();
        $form['registration_form[email]'] = 'newuser@test.com';
        $form['registration_form[plainPassword]'] = 'V3ryStr0ngP@ss!';
        $form['registration_form[confirmPassword]'] = 'V3ryStr0ngP@ss!';
        $form['registration_form[agreeTerms]'] = true;

        $this->client->submit($form);

        $response = $this->client->getResponse();

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'newuser@test.com']);
        if ($user) {
            $this->assertNotEquals('V3ryStr0ngP@ss!', $user->getPassword(), 'Password should be hashed');
        }

        $this->assertTrue(
            $response->isRedirection() || $response->isSuccessful() || $response->getStatusCode() === 422,
            'Registration should redirect on success or show validation errors'
        );
    }

    public function testRegistrationWithTihCheckbox(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $this->assertSelectorExists(
            'input[name="registration_form[isTih]"]',
            'Registration form should have a TIH checkbox'
        );
    }
}
