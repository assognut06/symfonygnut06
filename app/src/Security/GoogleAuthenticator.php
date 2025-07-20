<?php

namespace App\Security;

use App\Entity\User; // Assurez-vous que le chemin vers votre entité User est correct
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
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
use App\Service\EmailService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private UserPasswordHasherInterface $passwordHasher;
    private EmailService $emailService;
    private LoggerInterface $logger;
    private Security $security;
    private SessionInterface $session;
  
    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router, UserPasswordHasherInterface $passwordHasher, EmailService $emailService, LoggerInterface $logger, Security $security, RequestStack $requestStack)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->passwordHasher = $passwordHasher;
        $this->emailService = $emailService;
        $this->logger = $logger;
        $this->security = $security;
        $this->session = $requestStack->getSession();
       
    }

    /**
     * Détermine si cet authentificateur doit être utilisé pour la requête actuelle.
     * Il ne s'active que sur la route de callback de Google.
     */
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    /**
     * C'est ici que la logique d'authentification principale a lieu.
     */
    public function authenticate(Request $request): Passport
    {
        // Récupère le client OAuth2 'google' que nous avons configuré
        $client = $this->clientRegistry->getClient('google');
        // Récupère le jeton d'accès depuis la requête
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId();

                // 1. Cherche un utilisateur correspondant à ce googleId d'abord
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleId]);

                if ($existingUser) {
                    // L'utilisateur existe déjà avec ce Google ID, on le retourne
                    return $existingUser;
                }

                // 2. Cherche un utilisateur correspondant à cet e-mail dans notre base de données
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if ($existingUser) {
                    // L'utilisateur existe avec cet email, on lie le compte Google
                    $existingUser->setGoogleId($googleId);
                    $this->entityManager->persist($existingUser);
                    $this->entityManager->flush();
                    return $existingUser;
                }

                // 3. L'utilisateur n'existe pas, on le crée et on l'enregistre
                $newUser = new User();
                $newUser->setEmail($email);
                $newUser->setGoogleId($googleId);
                
                // Le mot de passe n'est pas nécessaire pour une connexion sociale,
                // mais notre entité User en requiert un. On lui assigne donc
                // une longue chaîne de caractères aléatoire et sécurisée.
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $newUser,
                    bin2hex(random_bytes(32))
                );
                $newUser->setPassword($hashedPassword);
                
                // Vous pouvez définir d'autres propriétés ici
                // Par exemple, si vous avez une propriété `fullName` :
                // $newUser->setFullName($googleUser->getName());
                // Ou pour les rôles :
                // $newUser->setRoles(['ROLE_USER']);

                $this->entityManager->persist($newUser);
                $this->entityManager->flush();

                if ($newUser) {
            // Si l'utilisateur n'est pas encore vérifié, on envoie l'email de confirmation
             try {
                $this->emailService->sendConfirmationEmail($newUser);
               // $this->addFlash('success', 'Un email de confirmation a été envoyé. Veuillez consulter votre boîte mail.');
            } catch (\Exception $e) {
                $this->logger->error('Erreur envoi email de confirmation', ['exception' => $e]);
               // $this->addFlash('danger', 'Problème lors de l\'envoi du mail. Veuillez réessayer.');
            }
           // return $this->redirectToRoute('app_profil');
            return $this->security->login($newUser, 'form_login', 'main');; // Retourne l'utilisateur connecté.
        }
        else{
          //  $this->addFlash('danger', "Erreur lors de l'authentification Google. Veuillez réessayer ou contacter l'administrateur.");
           // return $this->redirectToRoute('app_login');
        }

              //  return $this->redirectToRoute('app_profil');
            })
        );
    }

    /**
     * Appelé lorsque l'authentification réussit.
     * Redirige l'utilisateur vers la page d'accueil.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Changez 'app_home' pour le nom de la route vers laquelle vous voulez rediriger
        $targetUrl = $this->router->generate('app_profil');

        return new RedirectResponse($targetUrl);
    }

    /**
     * Appelé lorsque l'authentification échoue.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        // Vous pouvez rediriger vers la page de connexion avec un message d'erreur
       // $request->getSession()-> $this->addFlash('danger', $message);
        
        return new RedirectResponse(
            $this->router->generate('app_profil')
        );
    }
     public function __toString(): string
    {
        return self::class;
    }

    /**
     * Cette méthode est appelée lorsque l'utilisateur essaie d'accéder à une ressource
     * sécurisée sans être authentifié. Elle le redirige vers le processus de connexion Google.
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('connect_google_start'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
