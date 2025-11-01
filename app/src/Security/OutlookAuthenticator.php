<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
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
use TheNetworg\OAuth2\Client\Provider\AzureResourceOwner;

class OutlookAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router, UserPasswordHasherInterface $passwordHasher)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->passwordHasher = $passwordHasher;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_outlook_check';
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $client = $this->clientRegistry->getClient('azure');
            $accessToken = $this->fetchAccessToken($client);

            /** @var AzureResourceOwner $azureUser */
            $azureUser = $client->fetchUserFromToken($accessToken);

            // Récupérer les informations utilisateur
            $azureData = $azureUser->toArray();
            $azureId = $azureUser->getId();

            // Log des données reçues pour debug
            error_log('Azure user data: ' . json_encode($azureData));

            // Essayer différents champs pour récupérer l'email
            $email = $azureData['mail'] ??
                     $azureData['userPrincipalName'] ??
                     $azureData['email'] ??
                     $azureData['preferred_username'] ??
                     $azureData['upn'] ??           // User Principal Name (Microsoft)
                     $azureData['unique_name'] ??   // Nom unique (Microsoft)
                     null;

            if (!$email) {
                error_log('Aucun email trouvé dans les données Azure: ' . json_encode($azureData));
                throw new AuthenticationException('Impossible de récupérer l\'email depuis Microsoft Azure. Données reçues: ' . json_encode(array_keys($azureData)));
            }

            // Chercher l'utilisateur par azureId d'abord, puis par email
            $user = $this->entityManager->getRepository(User::class)
                ->findOneBy(['azureId' => $azureId]);

            if (!$user) {
                // Vérifier si un utilisateur avec cet email existe déjà
                $user = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if ($user) {
                    // Lier le compte existant avec Azure
                    $user->setAzureId($azureId);
                } else {
                    // Créer un nouvel utilisateur
                    $user = new User();
                    $user->setAzureId($azureId);
                    $user->setEmail($email);
                    $user->setRoles(['ROLE_USER']);

                    // Générer un mot de passe aléatoire pour les utilisateurs OAuth
                    $randomPassword = bin2hex(random_bytes(32));
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
                    $user->setPassword($hashedPassword);

                    // Marquer comme vérifié puisque Microsoft a déjà vérifié l'email
                    $user->setVerified(true);
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            return new SelfValidatingPassport(
                new UserBadge($user->getEmail(), function () use ($user) {
                    return $user;
                })
            );
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AuthenticationException('Erreur lors de la connexion avec Outlook: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Rediriger vers le profil utilisateur après connexion réussie
        $targetUrl = $this->router->generate('app_profil');
        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Log l'erreur pour le debug
        error_log('Outlook authentication failed: ' . $exception->getMessage());
        error_log('Request URI: ' . $request->getUri());
        error_log('Request parameters: ' . json_encode($request->query->all()));

        // Ajouter un message flash pour l'utilisateur
        if ($request->hasSession()) {
            $session = $request->getSession();
            $session->set('_flash_error', 'Erreur de connexion avec Microsoft: ' . $exception->getMessage());
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('connect_outlook_start'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    public function __toString(): string
    {
        return 'OutlookAuthenticator';
    }
}
