<?php

namespace App\Service;

use App\Application\DTO\Tih\TihContactDTO;
use App\Application\ViewModel\Tih\TihDetailsViewModel;
use App\Entity\Tih;
use App\Entity\TihApplicationEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class TihEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private LoggerInterface $logger,
        private RouterInterface $router,
        private string $fromEmail,
        private string $adminEmail,
        private string $logoPath
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function sendRejectionEmail(TihApplicationEvent $event): void
    {
        if (!$event->isRefusal()) {
            throw new \InvalidArgumentException('A rejection email requires a refusal event.');
        }

        $tih = $event->getTih();
        $recipient = $tih->getUser()?->getEmail();

        if (!$recipient) {
            throw new \InvalidArgumentException(
                sprintf('TIH #%d has no account email address', $tih->getId())
            );
        }

        $profileUrl = $this->router->generate('app_profil', [], UrlGeneratorInterface::ABSOLUTE_URL) . '#tih-rejection-message';
        $htmlContent = $this->twig->render('mailjet/tih_rejection.html.twig', [
            'firstName' => $tih->getFirstName(),
            'reason' => $event->getReason(),
            'profileUrl' => $profileUrl,
        ]);

        $email = (new Email())
            ->from(new Address($this->fromEmail, 'GNUT 06'))
            ->to($recipient)
            ->subject('Votre candidature TIH — Décision et motif')
            ->html($htmlContent);

        $this->mailer->send($email);

        $this->logger->info('TIH rejection email sent', [
            'tih_id' => $tih->getId(),
            'event_id' => $event->getId(),
            'recipient' => $recipient,
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendContactEmail(Tih $tih, TihContactDTO $contactData): void
    {
        $professionalEmail = $tih->getProfessionalEmail();
        
        if (!$professionalEmail) {
            throw new \InvalidArgumentException(
                sprintf('TIH #%d has no professional email address', $tih->getId())
            );
        }

        $tihViewModel = TihDetailsViewModel::fromEntity($tih);

        $htmlContent = $this->twig->render('mailjet/contact_tih.html.twig', [
            'data' => $contactData,
            'tih' => $tihViewModel,
        ]);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($professionalEmail)
            ->bcc($this->adminEmail)
            ->replyTo($contactData->email)
            ->subject($contactData->subject)
            ->html($htmlContent)
            ->embedFromPath($this->logoPath, 'logo-new');

        try {
            $this->mailer->send($email);
            
            $this->logger->info('Contact email sent to TIH', [
                'tih_id' => $tih->getId(),
                'tih_email' => $professionalEmail,
                'from_email' => $contactData->email,
                'from_name' => $contactData->prenom . ' ' . $contactData->nom,
                'subject' => $contactData->subject,
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send contact email to TIH', [
                'tih_id' => $tih->getId(),
                'tih_email' => $professionalEmail,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}
