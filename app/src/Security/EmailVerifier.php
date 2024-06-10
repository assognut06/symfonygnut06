<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Twig\Environment;

class EmailVerifier
{
    private VerifyEmailHelperInterface $verifyEmailHelper;
    private EntityManagerInterface $entityManager;
    private Environment $twig;

    public function __construct(
        VerifyEmailHelperInterface $verifyEmailHelper,
        EntityManagerInterface $entityManager,
        Environment $twig
    ) {
        $this->verifyEmailHelper = $verifyEmailHelper;
        $this->entityManager = $entityManager;
        $this->twig = $twig;
    }

    public function sendEmailConfirmation(string $verifyEmailRouteName, User $user, TemplatedEmail $email): void
    {
        // Générer la signature de l'URL de vérification
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            (string) $user->getId(),
            $user->getEmail()
        );

        // Préparer le contexte pour le template de l'email
        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        // Rendre le template de l'email en HTML avec le contexte préparé
        $emailContent = $this->twig->render($email->getHtmlTemplate(), $context);

        // Définir les paramètres de l'email
        $to = $user->getEmail();
        $subject = 'Please Confirm your Email';
        $headers = 'From: gnut@gnut06.org' . "\r\n" .
            'Reply-To: gnut@gnut06.org' . "\r\n" .
            'MIME-Version: 1.0' . "\r\n" .
            'Content-Type: text/html; charset=UTF-8' . "\r\n" .
            'Content-Transfer-Encoding: 8bit' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        // Encodage du sujet pour gérer les caractères spéciaux
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        // Envoyer l'email à l'aide de la fonction mail() de PHP
        if (!mail($to, $encodedSubject, $emailContent, $headers)) {
            throw new \RuntimeException('L\'envoi de l\'email a échoué.');
        }
    }
    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, User $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, (string) $user->getId(), $user->getEmail());

        $user->setVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
