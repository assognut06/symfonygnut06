<?php

namespace App\Tests\Unit\Security;

use App\Security\OAuth\GoogleIdTokenValidator;
use App\Security\OAuth\OAuthAccountException;
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
    private const KEY_ID = 'test-key';

    /** Test-only RSA key pair; never used outside this suite. */
    private const PRIVATE_KEY = <<<'PEM'
        -----BEGIN PRIVATE KEY-----
        MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDRB2ZMCXexa7Dc
        bqMBWrNylYxiyg3eeM74H3BcSS25Nz2w6d9w5GjHP1mlikHvZpSKNo/LW1i7mk16
        06jCr0LRI7PWgqfhU00pXj57HoOnzS/glHAWpKAAUG+XpZ8PEDj8vQ4WWVV6xdRy
        xPA0xRwFUnmV1y6+YkfyUx9oOH2QTMHNWZd3wyTHWZ8KmnNarw48wToWkh45qayn
        vmmpTSUBCMKOGLIT3epM/mswYfUomv5XVDtKlQxXJbWd/yAK0RGTVL22bkex5WQy
        m/NuTH8IK4+qZQyum02KiPeo6g3LNv+4rbwxjsJSFeWFSVz01PTgNE3KeoceO0bG
        Mj5BbCtLAgMBAAECggEAGCO7IB/dx0sRBzvtrjvbymlT55q/BEi+WjBDSR0YXzHu
        eW5g5Ag0w4Hg5/myCKQ3lkibzZfUhQHaXctwy17zH/T4EVdQbPiySgs8uvo4qRnM
        pCpwUWUcpzyizogNeO9eLW3l4RXbBc0v7jspJGb5B/JQ4UmS9+Cgv27zCxWvBolp
        PghisXvuI6K8K2/W31Si1Kq9Hpow3UK649c5g7P4qHtNo3p1vNYsJaxIzvjOmJMC
        HTFMP/yzFy/FyWmCg84KYO+6N0swttCyQK2YroowlsOovf656zu62SbDqGJSI63W
        HEgWZ9tMwdetXIEMNnSxo7v+F8dV0RJQH5uYbi1xmQKBgQDzkdtHTwatCAvcaHFf
        akRHmUSXHdTQLzWgKtpUSqrIejKFgZAjFm8veQVjU7EPlQP0bchZSAgQZdJXsGoU
        5ICb3mElzUhjO07g8jpdZCQ6/vvv07J/EgMDlByDRoYqEiG/izAk7yeYgjNfbFSV
        q1L5iQr/b1yTfKFNhsPDIsOEmQKBgQDbskfH0T7FqNk95pHsTt8XtJmDUT9YCZ8V
        0izg5L+bebsxGXN2NBzbvpByP4FNtX7Uu8j0au89zXn2IbIRPowv/tqxiDGLb+10
        vrzDtwVwbpVM4Qx+w5+Knzh0wFroiv+CO5ay+vnOIE36ID+XOPiEJUy53+FVyvbd
        ogFhCCt5gwKBgQDTgd8WxysW6pvSI+f/YTo1qoSDbWY1+ijpEw1QkR5IxMRGZsIR
        lhOq976UCEMDMvWiNgr6bLCD/MdxWkJkLiD4OV3HA8JOWVwfvnisTJ+hk3aXRhAE
        hFGVs/ImlQFAW0pvGKEQEZUivD18KYgyB/ofsr+YHM4ZTOqNde9c7j02UQKBgEtl
        PIMTiUJWNu+qYCvDyYYeIYzSZjW1X5YigepQNn2J4jbwcBKBweGb3YCH0L01ayhg
        pY9T33TLPm68k5qdZ4jVIoJIphAfQlONXcSg28oA+VXf6eTbB7aP+9T9anVhtlwg
        TRBxVydpKLmNNaWVFJxtHI6xiWhi9iOLhIOjRSA3AoGBAIY69kDK/p5ZjR92kap8
        ZIIvKFsKqid8u5HaX0AdDMkOghM3Y2TFN3sOzta2/SO0ctX3TnrUQjVIwkLVK7cG
        GwGu5wlixdJRQyermGbkrrSD34KtkUUUOlJepRBsTLD74guEHt2YonH9boU6OvsM
        swRlCMNhvDc1mzI6O76GSfEA
        -----END PRIVATE KEY-----
        PEM;

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
        $jwks = json_encode(['keys' => [$this->publicJwk()]], JSON_THROW_ON_ERROR);

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
            self::PRIVATE_KEY,
            'RS256',
            self::KEY_ID
        );
    }

    /** @return array<string, string> */
    private function publicJwk(): array
    {
        $key = openssl_pkey_get_private(self::PRIVATE_KEY);
        $this->assertNotFalse($key, 'Unable to read the test RSA key.');
        $details = openssl_pkey_get_details($key);
        $this->assertNotFalse($details, 'Unable to read the test RSA key details.');

        $encode = static fn (string $binary): string => rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');

        return [
            'kty' => 'RSA',
            'kid' => self::KEY_ID,
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => $encode($details['rsa']['n']),
            'e' => $encode($details['rsa']['e']),
        ];
    }
}
