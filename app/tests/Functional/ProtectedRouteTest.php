<?php

namespace App\Tests\Functional;

/**
 * Tests that authenticated-only routes properly redirect anonymous users
 * and are accessible to logged-in users.
 */
class ProtectedRouteTest extends WebTestCase
{
    /**
     * @dataProvider protectedUrlProvider
     */
    public function testProtectedRouteRedirectsAnonymous(string $url): void
    {
        $this->client->request('GET', $url);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isRedirection(),
            sprintf('Protected URL "%s" should redirect anonymous, got %d.', $url, $response->getStatusCode())
        );
    }

    public static function protectedUrlProvider(): iterable
    {
        yield 'Profile' => ['/profil'];
        yield 'TIH space' => ['/espace-tih'];
        yield 'Edit picture' => ['/profile/edit-picture'];
    }

    public function testProfileAccessibleWhenLoggedIn(): void
    {
        $this->loginAsNewUser();

        $this->client->request('GET', '/profil');

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirection(),
            sprintf('Profile should be accessible to logged-in users, got %d', $response->getStatusCode())
        );
        if ($response->isRedirection()) {
            $location = $response->headers->get('Location', '');
            $this->assertStringNotContainsString('login', $location);
        }
    }

    public function testTihSpaceAccessibleWhenLoggedIn(): void
    {
        $this->loginAsNewUser();

        $this->client->request('GET', '/espace-tih');

        $this->assertResponseIsSuccessful();
    }

    public function testEditPictureAccessibleWhenLoggedIn(): void
    {
        $this->loginAsNewUser();

        $this->client->request('GET', '/profile/edit-picture');

        $this->assertResponseIsSuccessful();
    }

    public function testDeletePictureRedirectsAnonymous(): void
    {
        $this->client->request('POST', '/profile/delete-picture');

        $this->assertResponseRedirectsToLogin();
    }

    public function testDeletePictureWorksForLoggedInUser(): void
    {
        $user = $this->loginAsNewUser();
        $user->setProfilePicture('test-photo.jpg');
        $this->em->flush();

        $this->submitDeleteProfilePicture($user->getId());

        $this->assertResponseRedirects('/profil');
    }
}
