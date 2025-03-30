<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Message\Notification;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use GuzzleHttp\Client;

class RegistrationController extends AbstractController
{

    public function __construct(
        private EmailVerifier $emailVerifier,
        private LoggerInterface $logger,
        private MessageBusInterface $bus,
    ) {
        $this->emailVerifier = $emailVerifier;
        $this->logger = $logger;
        $this->bus = $bus;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            // Vérifier la réponse reCAPTCHA
            $client = new Client();
            $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret' => $_ENV['NOCAPTCHA_SECRET'],
                    'response' => $recaptchaResponse,
                    'remoteip' => $request->getClientIp()
                ]
            ]);

            $responseData = json_decode($response->getBody());

            if ($_ENV['APP_ENV'] === 'dev') {
                $responseData->score = 0.9;
                $responseData->success = true;
            }

            if (!$responseData->success || $responseData->score < 0.5) {
                $this->addFlash('danger', 'La vérification reCAPTCHA a échoué. Veuillez réessayer.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                    'site_key' => $_ENV['NOCAPTCHA_SITEKEY']
                ]);
            } else {
                // encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );


                $entityManager->persist($user);
                $entityManager->flush();
                $sms = new Notification("L'inscription à été effectuée" . $user->getUserIdentifier());
                $this->bus->dispatch($sms);
                try {
                    // generate a signed url and email it to the user
                    $this->emailVerifier->sendEmailConfirmation(
                        'app_verify_email',
                        $user,
                        (new TemplatedEmail())
                            ->from(new Address('gnut@gnut06.org', 'Gnut 06'))
                            ->to($user->getEmail())
                            ->subject('Veuillez confirmer votre adresse e-mail sur Gnut 06.')
                            ->htmlTemplate('registration/confirmation_email.html.twig')
                    );

                    $this->addFlash('danger', 'Un email de confirmation a été envoyé à votre adresse email. Veuillez consulter votre boîte mail pour confirmer votre inscription.');
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors de l\'envoi de l\'email de confirmation d\'inscription', ['exception' => $e]);
                    $this->addFlash('danger', 'Un problème est survenu lors de l\'envoi de l\'email de confirmation d\'inscription. Veuillez réessayer.');
                }

                return $security->login($user, 'form_login', 'main');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'site_key' => $_ENV['NOCAPTCHA_SITEKEY']
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            $this->addFlash('success', 'Bonjours vous êtes déjà connecté \\n Vous avez reçu un mail de confirmation de votre inscription, veuillez le consulter pour confirmer votre inscription.');
            return $this->redirectToRoute('app_home');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app_home');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Votre adresse e-mail a été vérifiée.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/verify/email/renew', name: 'app_verify_email_renew')]
    public function renewUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            $this->addFlash('success', 'Bonjours vous êtes déjà connecté \\n Vous avez reçu un mail de confirmation de votre inscription, veuillez le consulter pour confirmer votre inscription.');
            return $this->redirectToRoute('app_home');
        }

        // generate a signed url and email it to the user
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('gnut@gnut06.org', 'Gnut 06'))
                ->to($user->getEmail())
                ->subject('Veuillez confirmer votre adresse e-mail sur Gnut 06.')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
        $this->addFlash('danger', 'Un email de confirmation a été envoyé à votre adresse email. Veuillez consulter votre boîte mail pour confirmer votre inscription.');

        return $this->redirectToRoute('app_home');
    }
}
