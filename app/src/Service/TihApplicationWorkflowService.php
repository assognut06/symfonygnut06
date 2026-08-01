<?php

namespace App\Service;

use App\Entity\Tih;
use App\Entity\TihApplicationEvent;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TihApplicationWorkflowService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TihEmailService $emailService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{event: TihApplicationEvent, created: bool}|null
     */
    public function refuse(int $tihId, string $reason, User $administrator): ?array
    {
        $outcome = $this->entityManager->wrapInTransaction(function () use ($tihId, $reason, $administrator): ?array {
            $tih = $this->entityManager->find(Tih::class, $tihId, LockMode::PESSIMISTIC_WRITE);

            if (!$tih instanceof Tih) {
                return null;
            }

            if (Tih::STATUS_REFUSED === $tih->getApplicationStatus()) {
                $existingEvent = $tih->getLatestRefusalEvent();

                if ($existingEvent instanceof TihApplicationEvent) {
                    return ['event' => $existingEvent, 'created' => false];
                }
            }

            $event = new TihApplicationEvent(
                $tih,
                TihApplicationEvent::STATUS_REFUSED,
                $administrator,
                $reason,
                TihApplicationEvent::SOURCE_ADMIN_DECISION,
            );
            $tih->addApplicationEvent($event);
            $tih->setApplicationStatus(Tih::STATUS_REFUSED);
            // Conservé temporairement pour la compatibilité avec les usages historiques.
            $tih->setValidationMessage($reason);

            $this->entityManager->persist($event);
            $this->entityManager->flush();

            return ['event' => $event, 'created' => true];
        });

        if (null !== $outcome && $outcome['created']) {
            $this->deliverRejectionEmail($outcome['event']);
        }

        return $outcome;
    }

    public function approve(int $tihId, User $administrator): ?bool
    {
        return $this->entityManager->wrapInTransaction(function () use ($tihId, $administrator): ?bool {
            $tih = $this->entityManager->find(Tih::class, $tihId, LockMode::PESSIMISTIC_WRITE);

            if (!$tih instanceof Tih) {
                return null;
            }

            if (Tih::STATUS_APPROVED === $tih->getApplicationStatus()) {
                return false;
            }

            $event = new TihApplicationEvent(
                $tih,
                TihApplicationEvent::STATUS_APPROVED,
                $administrator,
                source: TihApplicationEvent::SOURCE_ADMIN_DECISION,
            );
            $tih->addApplicationEvent($event);
            $tih->setApplicationStatus(Tih::STATUS_APPROVED);
            $tih->setValidationMessage(null);

            $this->entityManager->persist($event);
            $this->entityManager->flush();

            return true;
        });
    }

    public function submitForReview(Tih $tih, User $candidate, bool $initialSubmission): TihApplicationEvent
    {
        $event = new TihApplicationEvent(
            $tih,
            TihApplicationEvent::STATUS_PENDING,
            $candidate,
            source: $initialSubmission
                ? TihApplicationEvent::SOURCE_INITIAL_SUBMISSION
                : TihApplicationEvent::SOURCE_PROFILE_UPDATE,
        );

        $tih->addApplicationEvent($event);
        $tih->setApplicationStatus(Tih::STATUS_PENDING);
        $this->entityManager->persist($event);

        return $event;
    }

    public function retryRejectionEmail(int $eventId): string
    {
        $event = $this->entityManager->wrapInTransaction(function () use ($eventId): TihApplicationEvent|string|null {
            $applicationEvent = $this->entityManager->find(TihApplicationEvent::class, $eventId, LockMode::PESSIMISTIC_WRITE);

            if (!$applicationEvent instanceof TihApplicationEvent) {
                return null;
            }

            if (!$applicationEvent->isRefusal() || TihApplicationEvent::EMAIL_FAILED !== $applicationEvent->getEmailStatus()) {
                return 'not_failed';
            }

            $applicationEvent->markEmailPending();
            $this->entityManager->flush();

            return $applicationEvent;
        });

        if (null === $event) {
            return 'not_found';
        }

        if (is_string($event)) {
            return $event;
        }

        $this->deliverRejectionEmail($event);

        return (string) $event->getEmailStatus();
    }

    private function deliverRejectionEmail(TihApplicationEvent $event): void
    {
        try {
            $this->emailService->sendRejectionEmail($event);
            $event->markEmailSent();
        } catch (\Throwable $exception) {
            $event->markEmailFailed($exception->getMessage());
            $this->logger->error('Failed to send TIH rejection email', [
                'tih_id' => $event->getTih()->getId(),
                'event_id' => $event->getId(),
                'error' => $exception->getMessage(),
            ]);
        }

        $this->entityManager->flush();
    }
}
