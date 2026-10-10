<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Security\OAuth\OAuthAccountService;
use App\Security\OAuth\OAuthFlowManager;
use App\Security\OutlookAuthenticator;
use App\Tests\Fixtures\TestRsaKey;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use TheNetworg\OAuth2\Client\Provider\Azure;

/**
 * Runs the Microsoft login through the real routes, firewall, KnpU client, Azure provider and JWT
 * validation. Only Microsoft's HTTP endpoints are faked, with ID tokens signed by a local test key.
 */
final class MicrosoftOAuthFlowTest extends WebTestCase
{
    private const GNUT_TENANT = 'eab983d4-f55b-4a97-a9ba-de1d467df8de';
    private const CONSUMER_TENANT = '9188040d-6c67-4c5b-b112-36a304b66dad';
    private const WORK_TENANT = '22222222-3333-4444-5555-666666666666';
    private const GENERIC_FAILURE = 'La connexion avec Microsoft a échoué. Veuillez réessayer.';

    private string $tenant = self::WORK_TENANT;
    /** @var array<string, mixed> */
    private array $claimOverrides = [];
    private string $authorizeNonce = '';
    private string $clientId = '';
    private bool $invalidSignature = false;
    /** @var array<string, string>|null */
    private ?array $tokenRequest = null;

    public function testConfiguredTenantDefaultsToCommon(): void
    {
        $provider = static::getContainer()->get('knpu.oauth2.registry')->getClient('azure')->getOAuth2Provider();

        self::assertInstanceOf(Azure::class, $provider);
        self::assertSame('common', $provider->tenant);
        self::assertSame('2.0', $provider->defaultEndPointVersion);
    }

    public function testNewPersonalAccountIsCreatedVerifiedAndLoggedIn(): void
    {
        $this->useProvider();
        $this->tenant = self::CONSUMER_TENANT;

        $this->signIn();

        $this->assertResponseRedirects('/profil');
        $user = $this->findUserByAzureId();
        self::assertNotNull($user);
        self::assertSame('new.user@example.test', $user->getEmail());
        self::assertTrue($user->isVerified());

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testNewWorkAccountWithoutVerifiedDomainMustConfirmItsEmail(): void
    {
        $this->useProvider();

        $this->signIn();

        $this->assertResponseRedirects('/login');
        $this->assertEmailCount(1);
        $user = $this->findUserByAzureId();
        self::assertNotNull($user);
        self::assertFalse($user->isVerified());

        $this->client->followRedirect();
        self::assertStringContainsString('pas encore vérifié', $this->client->getCrawler()->text());
    }

    public function testNewWorkAccountWithVerifiedEmailDomainIsLoggedIn(): void
    {
        $this->useProvider();
        $this->claimOverrides = ['xms_edov' => true];

        $this->signIn();

        $this->assertResponseRedirects('/profil');
        self::assertTrue($this->findUserByAzureId()?->isVerified());
    }

    public function testLinkedAccountLogsInWithoutAnEmailClaim(): void
    {
        $this->useProvider();
        $user = $this->createUser('linked@test.com');
        $user->setAzureId($this->objectId());
        $this->em->flush();
        $this->claimOverrides = ['email' => null];

        $this->signIn();

        $this->assertResponseRedirects('/profil');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testEmailOfAPasswordAccountIsNeverLinkedAutomatically(): void
    {
        $this->useProvider();
        $this->createUser('new.user@example.test');

        $this->signIn();

        $this->assertResponseRedirects('/login');
        $this->em->clear();
        self::assertNull($this->findUserByAzureId());
        $this->client->followRedirect();
        self::assertStringContainsString('Un compte existe déjà avec cette adresse email', $this->client->getCrawler()->text());
    }

    public function testMicrosoftErrorRedirectShowsGenericFailureAndLogsTheErrorCode(): void
    {
        $logs = new TestHandler();
        $this->useProvider(logger: new Logger('test', [$logs]));
        $state = $this->startSignIn();

        $this->client->request('GET', '/connect/outlook/check', [
            'error' => 'invalid_request',
            'error_description' => "AADSTS50194: Application 'x' is not configured as a multi-tenant application.",
            'state' => $state,
        ]);

        $this->assertResponseRedirects('/login');
        self::assertNull($this->tokenRequest, 'No token exchange may happen without an authorization code.');
        $records = $logs->getRecords();
        self::assertCount(1, $records);
        self::assertSame('Microsoft OAuth callback failed.', $records[0]->message);
        self::assertSame(Level::Error, $records[0]->level);
        self::assertSame('AADSTS50194', $records[0]->context['provider_error']);

        $this->client->followRedirect();
        self::assertStringContainsString(self::GENERIC_FAILURE, $this->client->getCrawler()->text());
    }

    public function testNonceMismatchIsRejected(): void
    {
        $this->useProvider();
        $this->claimOverrides = ['nonce' => 'replayed-nonce'];

        $this->signIn();

        $this->assertResponseRedirects('/login');
        self::assertNull($this->findUserByAzureId());
        $this->client->followRedirect();
        self::assertStringContainsString('ne correspond pas à la demande de connexion', $this->client->getCrawler()->text());
    }

    public function testTokenForAnotherApplicationIsRejected(): void
    {
        $this->useProvider();
        $this->claimOverrides = ['aud' => '99999999-9999-9999-9999-999999999999'];

        $this->signIn();

        $this->assertResponseRedirects('/login');
        self::assertNull($this->findUserByAzureId());
        $this->client->followRedirect();
        self::assertStringContainsString(self::GENERIC_FAILURE, $this->client->getCrawler()->text());
    }

    public function testForgedStateIsRejectedBeforeAnyTokenExchange(): void
    {
        $this->useProvider();
        $this->startSignIn();

        $this->client->request('GET', '/connect/outlook/check', ['code' => 'auth-code', 'state' => 'forged']);

        $this->assertResponseRedirects('/login');
        self::assertNull($this->tokenRequest);
    }

    /** @return iterable<string, array{string}> */
    public static function accountTenants(): iterable
    {
        yield 'GNUT 06' => [self::GNUT_TENANT];
        yield 'another organization' => [self::WORK_TENANT];
        yield 'personal Microsoft account' => [self::CONSUMER_TENANT];
    }

    /** @dataProvider accountTenants */
    public function testCommonEndpointAcceptsAccountsFromAnyTenant(string $tenant): void
    {
        $this->useProvider();
        $this->tenant = $tenant;
        $this->claimOverrides = ['xms_edov' => true];

        $this->client->request('GET', '/connect/outlook');
        $location = (string) $this->client->getResponse()->headers->get('Location');
        self::assertStringStartsWith('https://login.microsoftonline.com/common/oauth2/v2.0/authorize?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        foreach (['openid', 'profile', 'email'] as $scope) {
            self::assertContains($scope, explode(' ', $query['scope']));
        }

        $this->completeSignIn($location);

        $this->assertResponseRedirects('/profil');
        self::assertNotNull($this->findUserByAzureId());
    }

    public function testNewAccountWithoutEmailIsRejected(): void
    {
        $this->useProvider();
        $this->claimOverrides = ['email' => null];

        $this->signIn();

        $this->assertResponseRedirects('/login');
        self::assertNull($this->findUserByAzureId());
        $this->assertEmailCount(0);
        $this->client->followRedirect();
        self::assertStringContainsString('Microsoft n’a pas fourni d’adresse email utilisable', $this->client->getCrawler()->text());
    }

    public function testIssuerFromAnotherTenantIsRejected(): void
    {
        $this->useProvider();
        $this->claimOverrides = ['iss' => 'https://login.microsoftonline.com/'.self::GNUT_TENANT.'/v2.0'];

        $this->signIn();

        $this->assertResponseRedirects('/login');
        self::assertNull($this->findUserByAzureId());
        $this->client->followRedirect();
        self::assertStringContainsString(self::GENERIC_FAILURE, $this->client->getCrawler()->text());
    }

    public function testTenantClaimMustMatchTheIssuer(): void
    {
        $this->useProvider();
        $this->claimOverrides = ['tid' => self::GNUT_TENANT];

        $this->signIn();

        $this->assertResponseRedirects('/login');
        self::assertNull($this->findUserByAzureId());
    }

    public function testInvalidSignatureIsRejected(): void
    {
        $this->useProvider();
        $this->invalidSignature = true;

        $this->signIn();

        $this->assertResponseRedirects('/login');
        self::assertNull($this->findUserByAzureId());
        $this->client->followRedirect();
        self::assertStringContainsString(self::GENERIC_FAILURE, $this->client->getCrawler()->text());
    }

    public function testMicrosoftLinkRequiresPasswordAndExplicitConfirmation(): void
    {
        $this->useProvider();
        $user = $this->loginAsNewUser();
        $userId = $user->getId();
        $token = $this->generateCsrfToken('oauth_link_authorize_microsoft');

        $this->client->request('POST', '/profil/oauth/link/microsoft/authorize', [
            '_token' => $token,
            'password' => 'wrong-password',
        ]);
        $this->assertResponseRedirects('/profil');
        self::assertNull($this->findUserByAzureId());

        $this->client->request('POST', '/profil/oauth/link/microsoft/authorize', [
            '_token' => $token,
            'password' => 'Test1234!',
        ]);
        $this->assertResponseRedirects('/connect/outlook');
        $this->signIn();

        $this->assertResponseRedirects('/profil/oauth/confirm');
        self::assertNull($this->findUserByAzureId(), 'The callback must not link before confirmation.');
        $token = $this->generateCsrfToken('oauth_link_confirm_microsoft');
        $this->client->request('POST', '/profil/oauth/confirm', ['_token' => $token]);

        $this->assertResponseRedirects('/profil');
        self::assertSame($userId, $this->findUserByAzureId()?->getId());
    }

    public function testLoggedInUserCannotStartAMicrosoftLogin(): void
    {
        $this->useProvider();
        $this->loginAsNewUser();

        $this->client->request('GET', '/connect/outlook');

        $this->assertResponseRedirects('/profil');
    }

    private function useProvider(?LoggerInterface $logger = null): void
    {
        $registry = static::getContainer()->get('knpu.oauth2.registry');
        self::assertInstanceOf(ClientRegistry::class, $registry);
        $provider = $registry->getClient('azure')->getOAuth2Provider();
        self::assertInstanceOf(Azure::class, $provider);
        self::assertSame('common', $provider->tenant);
        $this->clientId = $provider->getClientId();
        $provider->setHttpClient(new Client(['handler' => HandlerStack::create($this->fakeMicrosoft(...))]));
        $this->client->disableReboot();

        if (null !== $logger) {
            $services = static::getContainer();
            $services->set(OutlookAuthenticator::class, new OutlookAuthenticator(
                $registry,
                $services->get(OAuthAccountService::class),
                $services->get(OAuthFlowManager::class),
                $services->get('router'),
                $services->get('security.helper'),
                $logger,
            ));
        }
    }

    private function signIn(): void
    {
        $this->client->request('GET', '/connect/outlook');
        $this->completeSignIn((string) $this->client->getResponse()->headers->get('Location'));
    }

    private function startSignIn(): string
    {
        $this->client->request('GET', '/connect/outlook');
        parse_str((string) parse_url((string) $this->client->getResponse()->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->authorizeNonce = (string) ($query['nonce'] ?? '');

        return (string) $query['state'];
    }

    private function completeSignIn(string $authorizeUrl): void
    {
        parse_str((string) parse_url($authorizeUrl, PHP_URL_QUERY), $query);
        $this->authorizeNonce = (string) ($query['nonce'] ?? '');

        $this->client->request('GET', '/connect/outlook/check', ['code' => 'auth-code', 'state' => $query['state']]);
    }

    private function fakeMicrosoft(RequestInterface $request): \GuzzleHttp\Promise\PromiseInterface
    {
        $uri = (string) $request->getUri();
        $issuer = sprintf('https://login.microsoftonline.com/%s/v2.0', $this->tenant);

        if (str_contains($uri, '/.well-known/openid-configuration')) {
            $tenantInPath = explode('/', (string) parse_url($uri, PHP_URL_PATH))[1];

            return $this->json([
                'issuer' => 'common' === $tenantInPath ? 'https://login.microsoftonline.com/{tenantid}/v2.0' : sprintf('https://login.microsoftonline.com/%s/v2.0', $tenantInPath),
                'authorization_endpoint' => sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/authorize', $tenantInPath),
                'token_endpoint' => sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $tenantInPath),
                'jwks_uri' => 'https://login.microsoftonline.com/common/discovery/v2.0/keys',
            ]);
        }

        if (str_contains($uri, '/discovery/v2.0/keys')) {
            return $this->json(['keys' => [TestRsaKey::publicJwk()]]);
        }

        if (str_contains($uri, '/oauth2/v2.0/token')) {
            self::assertStringContainsString('/common/oauth2/v2.0/token', $uri);
            parse_str((string) $request->getBody(), $this->tokenRequest);
            $now = time();
            $claims = array_filter(array_merge([
                'aud' => $this->clientId,
                'iss' => $issuer,
                'iat' => $now,
                'nbf' => $now,
                'exp' => $now + 3600,
                'ver' => '2.0',
                'tid' => $this->tenant,
                'oid' => $this->objectId(),
                'email' => 'new.user@example.test',
                'nonce' => $this->authorizeNonce,
            ], $this->claimOverrides), static fn (mixed $value): bool => null !== $value);

            $idToken = JWT::encode($claims, TestRsaKey::PRIVATE_KEY, 'RS256', TestRsaKey::KID);
            if ($this->invalidSignature) {
                $parts = explode('.', $idToken);
                $parts[2][0] = 'A' === $parts[2][0] ? 'B' : 'A';
                $idToken = implode('.', $parts);
            }

            return $this->json([
                'token_type' => 'Bearer',
                'expires_in' => 3599,
                'access_token' => 'opaque-access-token',
                'id_token' => $idToken,
            ]);
        }

        throw new \RuntimeException('Unexpected request to '.$uri);
    }

    /** @param array<string, mixed> $body */
    private function json(array $body): \GuzzleHttp\Promise\PromiseInterface
    {
        return Create::promiseFor(new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($body)));
    }

    private function objectId(): string
    {
        return 'aaaaaaaa-bbbb-cccc-dddd-'.substr(md5($this->tenant), 0, 12);
    }

    private function findUserByAzureId(): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy(['azureId' => $this->objectId()]);
    }
}
