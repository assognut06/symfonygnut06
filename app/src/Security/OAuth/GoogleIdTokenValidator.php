<?php

namespace App\Security\OAuth;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** Validates Google-issued OIDC ID tokens; never trusts userinfo for identity. */
final class GoogleIdTokenValidator
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $googleClientId,
    ) {
    }

    /** @return array<string, mixed> */
    public function validate(string $idToken, string $nonce, bool $requireFreshAuthentication, int $minimumAuthenticationTime): array
    {
        if (($this->googleClientId ?? '') === '') {
            throw new OAuthAccountException('La connexion Google n’est pas configurée.');
        }

        try {
            $jwks = $this->httpClient->request('GET', 'https://www.googleapis.com/oauth2/v3/certs')->toArray();
            $claims = (array) JWT::decode($idToken, JWK::parseKeySet($jwks, 'RS256'));
        } catch (\Throwable $exception) {
            throw new OAuthAccountException('La réponse Google n’a pas pu être validée.', previous: $exception);
        }

        if (!in_array($claims['iss'] ?? null, ['https://accounts.google.com', 'accounts.google.com'], true)
            || !hash_equals((string) $this->googleClientId, (string) ($claims['aud'] ?? ''))
            || !hash_equals($nonce, (string) ($claims['nonce'] ?? ''))
            || !is_string($claims['sub'] ?? null)
            || $claims['sub'] === ''
        ) {
            throw new OAuthAccountException('La réponse Google ne correspond pas à la demande de connexion.');
        }

        if ($requireFreshAuthentication
            && (!is_numeric($claims['auth_time'] ?? null) || (int) $claims['auth_time'] < $minimumAuthenticationTime)) {
            throw new OAuthAccountException('Google n’a pas confirmé une authentification récente.');
        }

        return $claims;
    }
}
