<?php

namespace App\Security\OAuth;

use App\Entity\MicrosoftIdentityRepair;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class MicrosoftLegacyRepairService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
        private readonly string $fromEmail,
    ) {
    }

    public function send(User $user, OAuthIdentity $identity): void
    {
        $token = bin2hex(random_bytes(32));
        $repair = new MicrosoftIdentityRepair(
            $user,
            $user->getAzureId() ?? $identity->subject,
            $identity->tenantId ?? '',
            $identity->subject,
            hash('sha256', $token),
        );
        $this->entityManager->persist($repair);
        $this->entityManager->flush();

        $url = $this->router->generate('oauth_microsoft_legacy_repair', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->mailer->send((new TemplatedEmail())
            ->from(new Address($this->fromEmail, 'Gnut 06'))
            ->to($user->getEmail())
            ->subject('Confirmez la sécurisation de votre connexion Microsoft')
            ->htmlTemplate('oauth_link/microsoft_legacy_repair_email.html.twig')
            ->context(['repairUrl' => $url])
        );
        $this->logger->info('Microsoft legacy identity repair email sent.', ['user_id' => $user->getId()]);
    }

    public function findValid(string $token): ?MicrosoftIdentityRepair
    {
        $repair = $this->entityManager->getRepository(MicrosoftIdentityRepair::class)->findOneBy(['tokenHash' => hash('sha256', $token)]);
        return $repair instanceof MicrosoftIdentityRepair && $repair->canBeConsumed() ? $repair : null;
    }

    public function confirm(MicrosoftIdentityRepair $repair, OAuthAccountService $accountService): void
    {
        $this->entityManager->wrapInTransaction(function () use ($repair, $accountService): void {
            $identity = new OAuthIdentity(OAuthProvider::Microsoft, $repair->getAzureObjectId(), $repair->getAzureTenantId(), null, false);
            $accountService->confirmLegacyMicrosoftRepair($repair->getUser(), $identity);
            $repair->consume();
            $this->entityManager->flush();
        });
        $this->logger->info('Microsoft legacy identity repaired.', ['user_id' => $repair->getUser()->getId()]);
    }
}
