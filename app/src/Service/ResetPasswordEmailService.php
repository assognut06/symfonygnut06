<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ResetPasswordEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail,
    ) {}

    public function sendResetPasswordEmail(User $user, mixed $resetToken): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, 'Gnut 06'))
            ->to($user->getEmail())
            ->subject('Votre demande de réinitialisation de mot de passe sur Gnut 06')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ]);

        $this->mailer->send($email);
    }
}
