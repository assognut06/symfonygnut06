<?php

// src/Service/EmailService.php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;

class EmailService
{
    public function __construct(
        private EmailVerifier $emailVerifier,
        private string $fromEmail,
        private ?LoggerInterface $logger = null,
        private ?string $logoPath = null,
    ) {}

    public function sendConfirmationEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, 'Gnut 06'))
            ->to($user->getEmail())
            ->subject('Veuillez confirmer votre adresse e-mail sur Gnut 06.')
            ->htmlTemplate('registration/confirmation_email.html.twig');

        if ($this->logoPath && is_file($this->logoPath)) {
            $email->embedFromPath($this->logoPath, 'logo-new');
        }

        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user, $email);
        $this->logger?->info('Email de confirmation envoyé', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
        ]);
    }

    public function handleEmailConfirmation(Request $request, User $user): void
    {
        $this->emailVerifier->handleEmailConfirmation($request, $user);
    }
}
