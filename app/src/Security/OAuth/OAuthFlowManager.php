<?php

namespace App\Security\OAuth;

use App\Entity\User;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use Symfony\Component\HttpFoundation\Request;

/** Stores one short-lived OAuth transaction per browser session. */
final class OAuthFlowManager
{
    private const FLOW_KEY = '_oauth_flow';
    private const LINK_AUTHORIZATION_KEY = '_oauth_link_authorization';
    private const PENDING_LINK_KEY = '_oauth_pending_link';
    private const POST_AUTH_TARGET_KEY = '_oauth_post_auth_target';
    private const TTL = 600;

    /** @return array{provider: OAuthProvider, purpose: OAuthFlowPurpose, userId: ?int, targetProvider: ?OAuthProvider, nonce: string, issuedAt: int} */
    public function start(Request $request, OAuthProvider $provider): array
    {
        $this->purge($request);
        $session = $request->getSession();
        $existing = $session->get(self::FLOW_KEY);
        if (is_array($existing)
            && ($existing['provider'] ?? null) === $provider->value
            && ($existing['purpose'] ?? null) === OAuthFlowPurpose::Reauthenticate->value
            && ($existing['expiresAt'] ?? 0) >= time()
        ) {
            $existing['nonce'] = bin2hex(random_bytes(32));
            $session->set(self::FLOW_KEY, $existing);

            return $this->decode($existing);
        }

        $authorization = $session->remove(self::LINK_AUTHORIZATION_KEY);
        $flow = [
            'provider' => $provider->value,
            'purpose' => OAuthFlowPurpose::Login->value,
            'userId' => null,
            'targetProvider' => null,
            'nonce' => bin2hex(random_bytes(32)),
            'issuedAt' => time(),
            'expiresAt' => time() + self::TTL,
        ];
        if (is_array($authorization) && ($authorization['provider'] ?? null) === $provider->value && ($authorization['expiresAt'] ?? 0) >= time()) {
            $flow['purpose'] = OAuthFlowPurpose::Link->value;
            $flow['userId'] = $authorization['userId'] ?? null;
        }

        $this->replaceActiveFlow($request, $flow);

        return $this->decode($flow);
    }

    public function authorizeLink(Request $request, User $user, OAuthProvider $provider): void
    {
        $request->getSession()->set(self::LINK_AUTHORIZATION_KEY, [
            'provider' => $provider->value,
            'userId' => $user->getId(),
            'expiresAt' => time() + self::TTL,
        ]);
    }

    public function authorizeReauthentication(Request $request, User $user, OAuthProvider $reauthenticationProvider, OAuthProvider $targetProvider): void
    {
        $this->replaceActiveFlow($request, [
            'provider' => $reauthenticationProvider->value,
            'purpose' => OAuthFlowPurpose::Reauthenticate->value,
            'userId' => $user->getId(),
            'targetProvider' => $targetProvider->value,
            'nonce' => '',
            'issuedAt' => time(),
            'expiresAt' => time() + self::TTL,
        ]);
    }

    /** @return array{provider: OAuthProvider, purpose: OAuthFlowPurpose, userId: ?int, targetProvider: ?OAuthProvider, nonce: string, issuedAt: int} */
    public function requireFlow(Request $request, OAuthProvider $provider): array
    {
        $this->purge($request);
        $flow = $request->getSession()->get(self::FLOW_KEY);
        if (!is_array($flow) || ($flow['provider'] ?? null) !== $provider->value) {
            throw new OAuthAccountException('La demande de connexion a expiré. Veuillez recommencer.');
        }

        return $this->decode($flow);
    }

    public function clearFlow(Request $request, OAuthProvider $provider): void
    {
        $flow = $request->getSession()->get(self::FLOW_KEY);
        if (is_array($flow) && ($flow['provider'] ?? null) === $provider->value) {
            $request->getSession()->remove(self::FLOW_KEY);
        }
    }

    public function consumeProviderState(Request $request): void
    {
        $request->getSession()->remove(OAuth2Client::OAUTH2_SESSION_STATE_KEY);
    }

    public function completeReauthentication(Request $request, OAuthProvider $provider, OAuthProvider $targetProvider): void
    {
        $this->clearFlow($request, $provider);
        $request->getSession()->set(self::POST_AUTH_TARGET_KEY, $targetProvider->value);
    }

    public function consumePostAuthenticationTarget(Request $request): ?OAuthProvider
    {
        $target = $request->getSession()->remove(self::POST_AUTH_TARGET_KEY);

        return is_string($target) ? OAuthProvider::tryFrom($target) : null;
    }

    public function stageLink(Request $request, User $user, OAuthIdentity $identity): void
    {
        $request->getSession()->set(self::PENDING_LINK_KEY, [
            'userId' => $user->getId(),
            'provider' => $identity->provider->value,
            'subject' => $identity->subject,
            'email' => $identity->email,
            'emailVerified' => $identity->emailVerified,
            'expiresAt' => time() + self::TTL,
        ]);
    }

    public function pendingLink(Request $request, User $user): ?OAuthIdentity
    {
        $pending = $request->getSession()->get(self::PENDING_LINK_KEY);
        if (!is_array($pending) || ($pending['expiresAt'] ?? 0) < time() || ($pending['userId'] ?? null) !== $user->getId()) {
            return null;
        }

        return new OAuthIdentity(OAuthProvider::from($pending['provider']), $pending['subject'], $pending['email'], $pending['emailVerified']);
    }

    public function clearPendingLink(Request $request): void
    {
        $request->getSession()->remove(self::PENDING_LINK_KEY);
    }

    /** @param array<string, mixed> $flow */
    private function replaceActiveFlow(Request $request, array $flow): void
    {
        $request->getSession()->remove(OAuth2Client::OAUTH2_SESSION_STATE_KEY);
        $request->getSession()->set(self::FLOW_KEY, $flow);
    }

    private function purge(Request $request): void
    {
        $session = $request->getSession();
        foreach ([self::FLOW_KEY, self::LINK_AUTHORIZATION_KEY, self::PENDING_LINK_KEY] as $key) {
            $value = $session->get($key);
            if (is_array($value) && ($value['expiresAt'] ?? 0) < time()) {
                $session->remove($key);
            }
        }
    }

    /** @param array<string, mixed> $flow
     * @return array{provider: OAuthProvider, purpose: OAuthFlowPurpose, userId: ?int, targetProvider: ?OAuthProvider, nonce: string, issuedAt: int}
     */
    private function decode(array $flow): array
    {
        return [
            'provider' => OAuthProvider::from($flow['provider']),
            'purpose' => OAuthFlowPurpose::from($flow['purpose']),
            'userId' => isset($flow['userId']) ? (int) $flow['userId'] : null,
            'targetProvider' => isset($flow['targetProvider']) ? OAuthProvider::from($flow['targetProvider']) : null,
            'nonce' => (string) $flow['nonce'],
            'issuedAt' => (int) $flow['issuedAt'],
        ];
    }
}
