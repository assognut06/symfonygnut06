<?php
// src/Service/EmailService.php
namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use App\Security\EmailVerifier;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class EmailService
{
    public function __construct(private EmailVerifier $emailVerifier) {}

    public function sendConfirmationEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('gnut@gnut06.org', 'Gnut 06'))
            ->to($user->getEmail())
            ->subject('Veuillez confirmer votre adresse e-mail sur Gnut 06.')
            ->htmlTemplate('registration/confirmation_email.html.twig');

        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user, $email);
    }

    public function handleEmailConfirmation(Request $request, User $user): void
    {
        $this->emailVerifier->handleEmailConfirmation($request, $user);
    }
}