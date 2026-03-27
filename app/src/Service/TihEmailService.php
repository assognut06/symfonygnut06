<?php

namespace App\Service;

use App\Application\DTO\Tih\TihContactDTO;
use App\Application\ViewModel\Tih\TihDetailsViewModel;
use App\Entity\Tih;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class TihEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private LoggerInterface $logger,
        private string $fromEmail,
        private string $adminEmail,
        private string $logoPath
    ) {}

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
            ->cc($this->adminEmail)
            ->replyTo($contactData->email)
            ->subject($contactData->subject)
            ->html($htmlContent)
            ->embedFromPath($this->logoPath, 'eye-image');

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
