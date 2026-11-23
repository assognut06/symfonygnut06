<?php

namespace App\Tests\Unit\Security;

use App\Security\OAuth\GoogleIdTokenValidator;
use App\Security\OAuth\OAuthAccountException;
use App\Tests\Fixtures\TestRsaKey;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * The Google ID token is the only accepted proof of identity for Google login and
 * linking, so every claim it is trusted for is asserted here against a signed token.
 */
final class GoogleIdTokenValidatorTest extends TestCase
{
    private const CLIENT_ID = 'test-client-id.apps.googleusercontent.com';
    private const NONCE = 'nonce-value-123';

    public function testValidTokenReturnsTheImmutableSubject(): void
    {
        $claims = $this->validator()->validate($this->token(), self::NONCE, false, 0);

        $this->assertSame('1234567890', $claims['sub']);
        $this->assertSame('user@example.test', $claims['email']);
    }

    public function testFreshAuthenticationIsAcceptedWhenAuthTimeIsRecent(): void
    {
        $claims = $this->validator()->validate($this->token(), self::NONCE, true, time() - 60);

        $this->assertSame('1234567890', $claims['sub']);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string, 2: bool, 3: int}>
     */
    public static function rejectedTokenProvider(): iterable
    {
        $now = time();

        yield 'audience of another client' => [['aud' => 'another-client'], self::NONCE, false, 0];
        yield 'unexpected issuer' => [['iss' => 'https://evil.example'], self::NONCE, false, 0];
        yield 'replayed nonce' => [[], 'a-different-nonce', false, 0];
        yield 'empty subject' => [['sub' => ''], self::NONCE, false, 0];
        yield 'expired token' => [['exp' => $now - 10, 'iat' => $now - 3600], self::NONCE, false, 0];
        yield 'stale auth_time when freshness is required' => [['auth_time' => $now - 9999], self::NONCE, true, $now - 60];
        yield 'missing auth_time when freshness is required' => [['auth_time' => null], self::NONCE, true, $now - 60];
    }

    /**
     * @dataProvider rejectedTokenProvider
     *
     * @param array<string, mixed> $overrides
     */
    public function testInvalidTokensAreRejected(array $overrides, string $nonce, bool $requireFresh, int $minimumAuthTime): void
    {
        $this->expectException(OAuthAccountException::class);

        $this->validator()->validate($this->token($overrides), $nonce, $requireFresh, $minimumAuthTime);
    }

    public function testMalformedTokenIsRejected(): void
    {
        $this->expectException(OAuthAccountException::class);

        $this->validator()->validate('not-a-jwt', self::NONCE, false, 0);
    }

    /** An unconfigured client ID must never match an empty audience claim. */
    public function testUnconfiguredClientIdIsRejected(): void
    {
        $this->expectException(OAuthAccountException::class);

        $this->validator(null)->validate($this->token(['aud' => '']), self::NONCE, false, 0);
    }

    private function validator(?string $clientId = self::CLIENT_ID): GoogleIdTokenValidator
    {
        $jwks = json_encode(['keys' => [TestRsaKey::publicJwk()]], JSON_THROW_ON_ERROR);

        return new GoogleIdTokenValidator(
            new MockHttpClient(static fn (): MockResponse => new MockResponse($jwks)),
            $clientId
        );
    }

    /** @param array<string, mixed> $overrides */
    private function token(array $overrides = []): string
    {
        $now = time();
        $claims = array_merge([
            'iss' => 'https://accounts.google.com',
            'aud' => self::CLIENT_ID,
            'sub' => '1234567890',
            'nonce' => self::NONCE,
            'email' => 'user@example.test',
            'email_verified' => true,
            'iat' => $now,
            'exp' => $now + 3600,
            'auth_time' => $now,
        ], $overrides);

        return JWT::encode(
            array_filter($claims, static fn ($value): bool => null !== $value),
            TestRsaKey::PRIVATE_KEY,
            'RS256',
            TestRsaKey::KID
        );
    }
}
