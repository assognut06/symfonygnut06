<?php

namespace App\Security;

use App\Entity\User;
use App\Security\OAuth\LegacyMicrosoftRepairRequired;
use App\Security\OAuth\MicrosoftLegacyRepairService;
use App\Security\OAuth\OAuthAccountException;
use App\Security\OAuth\OAuthAccountService;
use App\Security\OAuth\OAuthFlowManager;
use App\Security\OAuth\OAuthFlowPurpose;
use App\Security\OAuth\OAuthIdentity;
use App\Security\OAuth\OAuthProvider;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class OutlookAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly OAuthAccountService $accountService,
        private readonly OAuthFlowManager $flowManager,
        private readonly MicrosoftLegacyRepairService $legacyRepairService,
        private readonly RouterInterface $router,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(Request $request): bool { return $request->attributes->get('_route') === 'connect_outlook_check'; }

    public function authenticate(Request $request): Passport
    {
        try {
            $flow = $this->flowManager->requireFlow($request, OAuthProvider::Microsoft);
            $accessToken = $this->fetchAccessToken($this->clientRegistry->getClient('azure'));
            $this->flowManager->consumeProviderState($request);
            $claims = $accessToken->getIdTokenClaims();
            if (!is_array($claims) || !hash_equals($flow['nonce'], (string) ($claims['nonce'] ?? ''))) {
                throw new OAuthAccountException('La réponse Microsoft ne correspond pas à la demande de connexion.');
            }
            $identity = new OAuthIdentity(
                OAuthProvider::Microsoft,
                $this->claim($claims, 'oid'),
                $this->claim($claims, 'tid'),
                is_string($claims['email'] ?? null) ? $claims['email'] : null,
                false,
            );

            return new SelfValidatingPassport(new UserBadge('microsoft:'.$identity->tenantId.':'.$identity->subject, function () use ($request, $flow, $identity): User {
                try {
                    return $this->resolveUser($request, $flow, $identity);
                } catch (LegacyMicrosoftRepairRequired $exception) {
                    $this->legacyRepairService->send($exception->user(), $exception->identity());
                    $this->flowManager->clearFlow($request, OAuthProvider::Microsoft);
                    throw new CustomUserMessageAuthenticationException($exception->getMessage());
                } catch (OAuthAccountException $exception) {
                    throw new CustomUserMessageAuthenticationException($exception->getMessage());
                }
            }));
        } catch (OAuthAccountException $exception) {
            throw new CustomUserMessageAuthenticationException($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->warning('Microsoft OAuth callback failed.', ['exception_type' => $exception::class]);
            throw new CustomUserMessageAuthenticationException('La connexion avec Microsoft a échoué. Veuillez réessayer.');
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        if ($target = $this->flowManager->consumePostAuthenticationTarget($request)) {
            return new RedirectResponse($this->router->generate($target->routeName()));
        }
        $user = $token->getUser();
        return new RedirectResponse($this->router->generate($user instanceof User && $this->flowManager->pendingLink($request, $user) !== null ? 'oauth_link_confirm' : 'app_profil'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $this->flowManager->clearFlow($request, OAuthProvider::Microsoft);
        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('error', strtr($exception->getMessageKey(), $exception->getMessageData()));
        }
        return new RedirectResponse($this->router->generate($this->security->getUser() instanceof User ? 'app_profil' : 'app_login'));
    }

    /** @param array{purpose: OAuthFlowPurpose, userId: ?int, targetProvider: ?OAuthProvider} $flow */
    private function resolveUser(Request $request, array $flow, OAuthIdentity $identity): User
    {
        if ($flow['purpose'] === OAuthFlowPurpose::Link) {
            $user = $this->currentUser($flow['userId']);
            $this->accountService->assertCanLink($user, $identity);
            $this->flowManager->stageLink($request, $user, $identity);
            $this->flowManager->clearFlow($request, OAuthProvider::Microsoft);
            return $user;
        }
        if ($flow['purpose'] === OAuthFlowPurpose::Reauthenticate) {
            $user = $this->currentUser($flow['userId']);
            $this->accountService->assertIdentityBelongsTo($user, $identity);
            $this->flowManager->authorizeLink($request, $user, $flow['targetProvider']);
            $this->flowManager->completeReauthentication($request, OAuthProvider::Microsoft, $flow['targetProvider']);
            return $user;
        }

        $user = $this->accountService->login($identity);
        $this->flowManager->clearFlow($request, OAuthProvider::Microsoft);
        return $user;
    }

    /** @param array<string, mixed> $claims */
    private function claim(array $claims, string $name): string
    {
        if (!is_string($claims[$name] ?? null) || $claims[$name] === '') {
            throw new OAuthAccountException('Microsoft n’a pas fourni '.$name.' utilisable.');
        }
        return $claims[$name];
    }

    private function currentUser(?int $expectedId): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || $user->getId() !== $expectedId) {
            throw new OAuthAccountException('Votre session a changé. Recommencez la liaison depuis votre profil.');
        }
        return $user;
    }
}
