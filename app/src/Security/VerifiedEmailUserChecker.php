<?php

namespace App\Security;

use App\Entity\User;
use App\Service\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class VerifiedEmailUserChecker implements UserCheckerInterface
{
    public function __construct(
        private EmailService $emailService,
        private LoggerInterface $logger,
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User || $user->isVerified()) {
            return;
        }

        try {
            $this->emailService->sendConfirmationEmail($user);
            $message = 'Votre compte n\'est pas encore vérifié. Un nouveau lien de validation vient de vous être envoyé par email.';
        } catch (\Throwable $exception) {
            $this->logger->error('Erreur renvoi email de confirmation pendant la connexion', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'exception' => $exception,
            ]);

            $message = 'Votre compte n\'est pas encore vérifié. Le renvoi du lien de validation a échoué, veuillez réessayer plus tard.';
        }

        throw new CustomUserMessageAccountStatusException($message);
    }
}
