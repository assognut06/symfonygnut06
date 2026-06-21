<?php

namespace App\Security;

use App\Entity\User;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private bool $oauthLinking = false;

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly RouterInterface $router,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EmailService $emailService,
        private readonly LoggerInterface $logger,
        private readonly Security $security,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client): User {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId();

                if (!$email || !$googleId) {
                    throw new AuthenticationException('Google n\'a pas transmis les informations nécessaires au compte.');
                }

                $linkedUser = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleId]);
                $currentUser = $this->security->getUser();

                if ($currentUser instanceof User) {
                    if ($linkedUser && $linkedUser->getId() !== $currentUser->getId()) {
                        throw new AuthenticationException('Ce compte Google est déjà lié à un autre utilisateur.');
                    }

                    if ($currentUser->getGoogleId() && $currentUser->getGoogleId() !== $googleId) {
                        throw new AuthenticationException('Votre profil est déjà lié à un autre compte Google.');
                    }

                    $currentUser->setGoogleId($googleId);
                    $this->entityManager->flush();
                    $this->oauthLinking = true;

                    return $currentUser;
                }

                if ($linkedUser) {
                    return $linkedUser;
                }

                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if ($existingUser) {
                    throw new AuthenticationException('Un compte existe déjà avec cet email. Connectez-vous avec votre mot de passe, puis liez Google depuis votre profil.');
                }

                $newUser = new User();
                $newUser->setEmail($email);
                $newUser->setGoogleId($googleId);
                $newUser->setRoles(['ROLE_USER']);
                $newUser->setPassword($this->passwordHasher->hashPassword($newUser, bin2hex(random_bytes(32))));

                $googleData = $googleUser->toArray();
                $newUser->setVerified(($googleData['email_verified'] ?? false) === true);

                $this->entityManager->persist($newUser);
                $this->entityManager->flush();

                if (!$newUser->isVerified()) {
                    try {
                        $this->emailService->sendConfirmationEmail($newUser);
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur envoi email de confirmation', ['exception' => $e]);
                    }
                }

                return $newUser;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($this->oauthLinking) {
            $request->getSession()->getFlashBag()->add('success', 'Votre compte Google est lié à votre profil.');
        }

        return new RedirectResponse($this->router->generate('app_profil'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add('danger', $exception->getMessage());
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('connect_google_start'), Response::HTTP_TEMPORARY_REDIRECT);
    }
}
