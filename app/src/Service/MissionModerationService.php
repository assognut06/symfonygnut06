<?php

namespace App\Service;

use App\Entity\DecisionModeration;
use App\Entity\Mission;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MissionModerationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function canPublish(Mission $mission): bool
    {
        if ($mission->getStatut() !== Mission::STATUS_PENDING) {
            return false;
        }

        if (!$mission->getCompteEntreprise()->isApproved()) {
            return false;
        }

        $deadline = $mission->getDateLimiteReponse();

        if (
            $deadline === null
            || $deadline->format('Y-m-d')
                <= (new \DateTimeImmutable('today'))->format('Y-m-d')
        ) {
            return false;
        }

        $violations = $this->validator->validate(
            $mission,
            null,
            ['Default', 'mission_submit']
        );

        return count($violations) === 0;
    }

    public function publish(
        Mission $mission,
        User $moderator
    ): void {
        if (!$this->canPublish($mission)) {
            throw new \DomainException(
                'Cette mission ne remplit pas les conditions de publication.'
            );
        }

        $mission->publish();

        $decision = new DecisionModeration(
            $mission,
            $moderator,
            DecisionModeration::DECISION_PUBLISH
        );

        $this->entityManager->persist($decision);
        $this->entityManager->flush();
    }

    public function requestCorrection(
        Mission $mission,
        User $moderator,
        string $reason
    ): void {
        $reason = $this->validateReason($reason);

        $mission->requestCorrection();

        $decision = new DecisionModeration(
            $mission,
            $moderator,
            DecisionModeration::DECISION_REQUEST_CORRECTION,
            $reason
        );

        $this->entityManager->persist($decision);
        $this->entityManager->flush();
    }

    public function withdraw(
        Mission $mission,
        User $moderator,
        string $reason
    ): void {
        $reason = $this->validateReason($reason);

        $mission->withdraw();

        $decision = new DecisionModeration(
            $mission,
            $moderator,
            DecisionModeration::DECISION_WITHDRAW,
            $reason
        );

        $this->entityManager->persist($decision);
        $this->entityManager->flush();
    }

    private function validateReason(string $reason): string
    {
        $reason = trim($reason);

        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw new \DomainException(
                'Le motif est obligatoire et ne doit pas dépasser 2 000 caractères.'
            );
        }

        return $reason;
    }
}