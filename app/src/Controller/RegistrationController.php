<?php

namespace App\Controller;

use App\Application\Command\RegisterUserCommand;
use App\Application\DTO\RegisterUserDTO;
use App\Domain\ValueObject\UserType;
use App\Entity\Tih;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Message\Notification;
use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\RecaptchaVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private MessageBusInterface $bus,
        private readonly string $nocaptchaSiteKey
    ) {}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        RecaptchaVerifier $recaptchaVerifier,
        EmailService $emailService,
    ): Response {
        $dto = new RegisterUserDTO();
        $form = $this->createForm(RegistrationFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$recaptchaVerifier->verify($request)) {
                $this->addFlash('danger', 'La vérification reCAPTCHA a échoué. Veuillez réessayer.');

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                    'site_key' => $this->nocaptchaSiteKey
                ]);
            }

            // Create command from DTO with proper value object
            $command = new RegisterUserCommand(
                email: $dto->email,
                plainPassword: $dto->plainPassword,
                userType: $dto->isTih ? UserType::tih() : UserType::regular()
            );

            $user = new User();
            $user->setEmail($dto->email);
            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $dto->plainPassword)
            );

            if($dto->isTih === true) {
                $user->setTih(new Tih());
            }
            $entityManager->persist($user);
            $entityManager->flush();

            $this->bus->dispatch(new Notification("L'inscription a été effectuée: ".$user->getUserIdentifier()));

            try {
                $emailService->sendConfirmationEmail($user);
                $this->addFlash('success', 'Votre compte a été créé. Un email de confirmation vous a été envoyé : validez votre adresse avant de vous connecter.');
            } catch (\Exception $e) {
                $this->logger->error('Erreur envoi email de confirmation', ['exception' => $e]);
                $this->addFlash('danger', 'Problème lors de l\'envoi du mail. Veuillez réessayer.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'site_key' => $this->nocaptchaSiteKey
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository, EmailService $emailService): Response
    {
        $id = $request->query->get('id');

        if (!$id || !is_numeric($id)) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (!$user) {
            $this->addFlash('info', 'Utilisateur déjà connecté. Vérifiez vos emails pour confirmer.');

            return $this->redirectToRoute('app_home');
        }

        try {
            $emailService->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $e) {
            try {
                $emailService->sendConfirmationEmail($user);
                $this->addFlash('verify_email_error', sprintf(
                    '%s Un nouveau lien de validation vient de vous être envoyé.',
                    $translator->trans($e->getReason(), [], 'VerifyEmailBundle')
                ));
            } catch (\Exception $mailException) {
                $this->logger->error('Erreur renvoi email de confirmation apres lien invalide', [
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'exception' => $mailException,
                ]);
                $this->addFlash('verify_email_error', $translator->trans($e->getReason(), [], 'VerifyEmailBundle'));
            }

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Votre adresse e-mail a été vérifiée.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/verify/email/renew', name: 'app_verify_email_renew')]
    public function renewUserEmail(Request $request, UserRepository $userRepository, EmailService $emailService): Response
    {
        $id = $request->query->get('id');
        if (!$id || !is_numeric($id)) {
            return $this->redirectToRoute('app_register');
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || $currentUser->getId() !== (int) $id) {
            $this->addFlash('danger', 'Impossible de renvoyer un email de confirmation pour un autre compte.');

            return $this->redirectToRoute('app_profil');
        }

        $user = $userRepository->find($currentUser->getId());
        if (!$user) {
            $this->addFlash('info', 'Utilisateur non trouvé ou déjà vérifié.');

            return $this->redirectToRoute('app_home');
        }

        try {
            $emailService->sendConfirmationEmail($user);
            $this->addFlash('info', sprintf(
                'Un nouvel email de confirmation a été envoyé à %s.',
                $user->getEmail()
            ));
        } catch (\Exception $e) {
            $this->logger->error('Erreur renvoi email de confirmation', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'exception' => $e,
            ]);
            $this->addFlash('danger', 'Problème lors de l\'envoi du mail de confirmation. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_home');
    }
}
