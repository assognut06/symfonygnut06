<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class TihApplicationEvent
{
    public const STATUS_PENDING = Tih::STATUS_PENDING;
    public const STATUS_APPROVED = Tih::STATUS_APPROVED;
    public const STATUS_REFUSED = Tih::STATUS_REFUSED;

    public const EMAIL_PENDING = 'pending';
    public const EMAIL_SENT = 'sent';
    public const EMAIL_FAILED = 'failed';

    public const SOURCE_INITIAL_SUBMISSION = 'initial_submission';
    public const SOURCE_PROFILE_UPDATE = 'profile_update';
    public const SOURCE_ADMIN_DECISION = 'admin_decision';
    public const SOURCE_LEGACY_MIGRATION = 'legacy_migration';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'applicationEvents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tih $tih;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $actor = null;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $emailStatus = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailSentAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $emailError = null;

    public function __construct(
        Tih $tih,
        string $status,
        ?User $actor,
        ?string $reason = null,
        ?string $source = null,
    ) {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REFUSED], true)) {
            throw new \InvalidArgumentException(sprintf('Unknown TIH application event status "%s".', $status));
        }

        if (self::STATUS_REFUSED === $status && (null === $reason || '' === trim($reason))) {
            throw new \InvalidArgumentException('A rejection event requires a reason.');
        }

        if (self::STATUS_REFUSED !== $status && null !== $reason) {
            throw new \InvalidArgumentException('Only a rejection event may contain a reason.');
        }

        $this->tih = $tih;
        $this->status = $status;
        $this->actor = $actor;
        $this->reason = $reason;
        $this->source = $source;
        $this->occurredAt = new \DateTimeImmutable();
        $this->emailStatus = self::STATUS_REFUSED === $status ? self::EMAIL_PENDING : null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTih(): Tih
    {
        return $this->tih;
    }

    public function setTih(Tih $tih): self
    {
        $this->tih = $tih;

        return $this;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getEmailStatus(): ?string
    {
        return $this->emailStatus;
    }

    public function getEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->emailSentAt;
    }

    public function getEmailError(): ?string
    {
        return $this->emailError;
    }

    public function isRefusal(): bool
    {
        return self::STATUS_REFUSED === $this->status;
    }

    public function markEmailPending(): self
    {
        $this->guardRefusal();
        $this->emailStatus = self::EMAIL_PENDING;
        $this->emailError = null;

        return $this;
    }

    public function markEmailSent(): self
    {
        $this->guardRefusal();
        $this->emailStatus = self::EMAIL_SENT;
        $this->emailSentAt = new \DateTimeImmutable();
        $this->emailError = null;

        return $this;
    }

    public function markEmailFailed(string $error): self
    {
        $this->guardRefusal();
        $this->emailStatus = self::EMAIL_FAILED;
        $this->emailError = mb_substr($error, 0, 2000);

        return $this;
    }

    private function guardRefusal(): void
    {
        if (!$this->isRefusal()) {
            throw new \LogicException('Email delivery is only available for rejection events.');
        }
    }
}
