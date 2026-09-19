<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\OAuth\OAuthAccountException;
use App\Security\OAuth\OAuthAccountService;
use App\Security\OAuth\OAuthFlowManager;
use App\Security\OAuth\OAuthProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profil/oauth')]
final class OAuthLinkController extends AbstractController
{
    public function __construct(
        private readonly OAuthFlowManager $flowManager,
        private readonly OAuthAccountService $accountService,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/link/{provider}', name: 'oauth_link_reauth', methods: ['POST'])]
    public function reauth(Request $request, string $provider): Response
    {
        $user = $this->user();
        $provider = $this->provider($provider);
        if (!$this->isCsrfTokenValid('oauth_link_start_'.$provider->value, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('oauth_link/reauth.html.twig', ['provider' => $provider, 'user' => $user]);
    }

    #[Route('/link/{provider}/authorize', name: 'oauth_link_authorize', methods: ['POST'])]
    public function authorize(Request $request, string $provider): RedirectResponse
    {
        $user = $this->user();
        $provider = $this->provider($provider);
        if (!$this->isCsrfTokenValid('oauth_link_authorize_'.$provider->value, (string) $request->request->get('_token'))
            || !$this->passwordHasher->isPasswordValid($user, (string) $request->request->get('password'))) {
            $this->addFlash('error', 'La confirmation du mot de passe a échoué.');
            return $this->redirectToRoute('app_profil');
        }

        $this->flowManager->authorizeLink($request, $user, $provider);
        return $this->redirectToRoute($provider->routeName());
    }

    #[Route('/link/{provider}/reauthenticate/{reauthenticationProvider}', name: 'oauth_link_provider_reauth', methods: ['POST'])]
    public function providerReauth(Request $request, string $provider, string $reauthenticationProvider): RedirectResponse
    {
        $user = $this->user();
        $provider = $this->provider($provider);
        $reauthenticationProvider = $this->provider($reauthenticationProvider);
        if (!$this->isCsrfTokenValid('oauth_link_provider_reauth_'.$provider->value.'_'.$reauthenticationProvider->value, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->flowManager->authorizeReauthentication($request, $user, $reauthenticationProvider, $provider);
        return $this->redirectToRoute($reauthenticationProvider->routeName());
    }

    #[Route('/confirm', name: 'oauth_link_confirm', methods: ['GET'])]
    public function confirm(Request $request): Response
    {
        $pending = $this->flowManager->pendingLink($request, $this->user());
        if ($pending === null) {
            $this->addFlash('error', 'La demande de liaison a expiré.');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('oauth_link/confirm.html.twig', ['identity' => $pending]);
    }

    #[Route('/confirm', name: 'oauth_link_confirm_submit', methods: ['POST'])]
    public function confirmSubmit(Request $request): RedirectResponse
    {
        $user = $this->user();
        $identity = $this->flowManager->pendingLink($request, $user);
        if ($identity === null || !$this->isCsrfTokenValid('oauth_link_confirm_'.$identity->provider->value, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $this->accountService->confirmLink($user, $identity);
            $this->addFlash('success', 'La connexion OAuth a été liée à votre compte.');
        } catch (OAuthAccountException $exception) {
            $this->addFlash('error', $exception->getMessage());
        } finally {
            $this->flowManager->clearPendingLink($request);
        }

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/cancel', name: 'oauth_link_cancel', methods: ['POST'])]
    public function cancel(Request $request): RedirectResponse
    {
        $identity = $this->flowManager->pendingLink($request, $this->user());
        if ($identity === null || !$this->isCsrfTokenValid('oauth_link_cancel_'.$identity->provider->value, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $this->flowManager->clearPendingLink($request);
        return $this->redirectToRoute('app_profil');
    }

    private function user(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        return $user;
    }

    private function provider(string $provider): OAuthProvider
    {
        try {
            return OAuthProvider::from($provider);
        } catch (\ValueError) {
            throw $this->createNotFoundException();
        }
    }
}
