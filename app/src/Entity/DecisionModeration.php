<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'decision_moderation')]
class DecisionModeration
{
    public const DECISION_PUBLISH = 'publish';
    public const DECISION_REQUEST_CORRECTION = 'request_correction';
    public const DECISION_WITHDRAW = 'withdraw';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Mission::class)]
    #[ORM\JoinColumn(
        name: 'mission_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private Mission $mission;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'moderator_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private User $moderator;

    #[ORM\Column(length: 30)]
    private string $decision;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Mission $mission,
        User $moderator,
        string $decision,
        ?string $reason = null
    ) {
        $allowedDecisions = [
            self::DECISION_PUBLISH,
            self::DECISION_REQUEST_CORRECTION,
            self::DECISION_WITHDRAW,
        ];

        if (!in_array($decision, $allowedDecisions, true)) {
           
         throw new \InvalidArgumentException(
                'Décision de modération invalide.'
            );
        }

        $reason = $reason !== null ? trim($reason) : null;
        $reason = $reason === '' ? null : $reason;

        if (
            in_array(
                $decision,
                [
                    self::DECISION_REQUEST_CORRECTION,
                    self::DECISION_WITHDRAW,
                ],
                true
            )
            && $reason === null
        ) {
            throw new \InvalidArgumentException(
                'Un motif est obligatoire pour cette décision.'
            );
        }

        $this->mission = $mission;
        $this->moderator = $moderator;
        $this->decision = $decision;
        $this->reason = $reason;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function getModerator(): User
    {
        return $this->moderator;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isPublication(): bool
    {
        return $this->decision === self::DECISION_PUBLISH;
    }

    public function isCorrectionRequest(): bool
    {
        return $this->decision === self::DECISION_REQUEST_CORRECTION;
    }

    public function isWithdrawal(): bool
    {
        return $this->decision === self::DECISION_WITHDRAW;
    }
}