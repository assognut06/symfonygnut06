<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Message\Notification;
use App\Repository\UserRepository;
use App\Service\RecaptchaVerifier;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
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
        private string $appEnv ,
        private string $recaptchaSecret,
    ) {}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager,
        RecaptchaVerifier $recaptchaVerifier,
        EmailService $emailService
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recaptchaToken = $request->request->get('g-recaptcha-response');
            $ip = $request->getClientIp();

            if (!$recaptchaVerifier->verify($request)) {
                $this->addFlash('danger', 'La vérification reCAPTCHA a échoué. Veuillez réessayer.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                    'site_key' => $_ENV['NOCAPTCHA_SITEKEY']
                ]);
            }

            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );
            $entityManager->persist($user);
            $entityManager->flush();

            $this->bus->dispatch(new Notification("L'inscription a été effectuée: " . $user->getUserIdentifier()));

            try {
                $emailService->sendConfirmationEmail($user);
                $this->addFlash('success', 'Un email de confirmation a été envoyé. Veuillez consulter votre boîte mail.');
            } catch (\Exception $e) {
                $this->logger->error('Erreur envoi email de confirmation', ['exception' => $e]);
                $this->addFlash('danger', 'Problème lors de l\'envoi du mail. Veuillez réessayer.');
            }

            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'site_key' => $_ENV['NOCAPTCHA_SITEKEY']
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
            $this->addFlash('verify_email_error', $translator->trans($e->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app_home');
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

        $user = $userRepository->find($id);
        if (!$user) {
            $this->addFlash('info', 'Utilisateur non trouvé ou déjà vérifié.');
            return $this->redirectToRoute('app_home');
        }

        $emailService->sendConfirmationEmail($user);
        $this->addFlash('info', 'Un nouvel email de confirmation a été envoyé.');
        return $this->redirectToRoute('app_home');
    }
}