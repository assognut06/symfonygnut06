<?php

namespace App\Tests\Controller;

use App\Service\HelloAssoApiService;
use App\Tests\Functional\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminAccessTest extends WebTestCase
{
    public function testAnonymousUserIsRedirectedFromAdminArea(): void
    {
        $this->client->request('GET', '/admin');

        self::assertRedirectedToLoginOrForbidden($this->client);
    }

    public function testRegularUserCannotAccessAdminArea(): void
    {
        $this->loginAsNewUser();

        $this->client->request('GET', '/admin');

        self::assertRedirectedToLoginOrForbidden($this->client);
    }

    public function testAdminUserCanAccessAdminDashboard(): void
    {
        $this->client->getContainer()->set(HelloAssoApiService::class, new class extends HelloAssoApiService {
            public function __construct()
            {
            }

            public function makeApiCall(string $url, array $headers = [], string $method = 'GET')
            {
                return [
                    'name' => 'GNUT 06',
                    'logo' => '/images/LogoNew.png',
                    'description' => 'Association de test.',
                    'type' => 'Association1901Rig',
                    'category' => 'Solidarite',
                    'zipCode' => '06000',
                    'city' => 'Nice',
                    'rnaNumber' => 'W000000000',
                    'url' => 'https://example.test',
                ];
            }
        });
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
    }

    public function testSensitiveRoutesOutsideAdminAreExplicitlyProtected(): void
    {
        $this->loginAsNewUser();

        $this->client->request('GET', '/oauth-test/');

        self::assertRedirectedToLoginOrForbidden($this->client);
    }

    public function testAllAdminControllersDeclareAdminRoleRequirement(): void
    {
        $controllerFiles = glob(dirname(__DIR__, 2).'/src/Controller/*Admin*Controller.php');

        self::assertNotEmpty($controllerFiles);

        foreach ($controllerFiles as $controllerFile) {
            $className = 'App\\Controller\\'.basename($controllerFile, '.php');
            $reflection = new \ReflectionClass($className);
            $attributes = $reflection->getAttributes(IsGranted::class);

            self::assertNotEmpty($attributes, sprintf('%s must declare an IsGranted attribute.', $className));
            self::assertContains(
                'ROLE_ADMIN',
                array_map(static fn (\ReflectionAttribute $attribute): string => $attribute->newInstance()->attribute, $attributes),
                sprintf('%s must require ROLE_ADMIN.', $className)
            );
        }
    }

    private static function assertRedirectedToLoginOrForbidden($client): void
    {
        $response = $client->getResponse();
        $statusCode = $response->getStatusCode();

        self::assertContains($statusCode, [
            Response::HTTP_FOUND,
            Response::HTTP_SEE_OTHER,
            Response::HTTP_FORBIDDEN,
        ]);

        if ($response->isRedirection()) {
            $location = $response->headers->get('Location', '');

            self::assertTrue(
                str_contains($location, '/login') || str_contains($location, '/profil'),
                sprintf('Expected a security redirect, got "%s".', $location)
            );
        }
    }
}
