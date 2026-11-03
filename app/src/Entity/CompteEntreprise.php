<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'compte_entreprise')]
#[ORM\HasLifecycleCallbacks]
class CompteEntreprise
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'user_id',
        referencedColumnName: 'id',
        nullable: false,
        unique: true
    )]
    private User $user;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 160)]
    private string $raisonSociale = '';

    #[ORM\Column(length: 14, nullable: true)]
    #[Assert\Regex(
        pattern: '/^(?:[0-9]{9}|[0-9]{14})$/',
        message: 'Le SIREN doit contenir 9 chiffres ou le SIRET 14 chiffres.'
    )]
    private ?string $identifiantLegal = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private string $nomContact = '';

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private string $emailContact = '';

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $telephone = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_SUSPENDED,
    ])]
    private string $statut = self::STATUS_PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRaisonSociale(): string
    {
        return $this->raisonSociale;
    }

    public function setRaisonSociale(string $value): self
    {
        $this->raisonSociale = trim($value);

        return $this;
    }

    public function getIdentifiantLegal(): ?string
    {
        return $this->identifiantLegal;
    }

    public function setIdentifiantLegal(?string $value): self
    {
        $this->identifiantLegal = $value === null || trim($value) === ''
            ? null
            : trim($value);

        return $this;
    }

    public function getNomContact(): string
    {
        return $this->nomContact;
    }

    public function setNomContact(string $value): self
    {
        $this->nomContact = trim($value);

        return $this;
    }

    public function getEmailContact(): string
    {
        return $this->emailContact;
    }

    public function setEmailContact(string $value): self
    {
        $this->emailContact = trim($value);

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $value): self
    {
        $this->telephone = $value === null || trim($value) === ''
            ? null
            : trim($value);

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function isApproved(): bool
    {
        return $this->statut === self::STATUS_APPROVED;
    }

    public function approve(): void
    {
        $this->transitionTo(self::STATUS_APPROVED, [
            self::STATUS_PENDING,
            self::STATUS_SUSPENDED,
        ]);
    }

    public function reject(): void
    {
        $this->transitionTo(self::STATUS_REJECTED, [
            self::STATUS_PENDING,
        ]);
    }

    public function suspend(): void
    {
        $this->transitionTo(self::STATUS_SUSPENDED, [
            self::STATUS_APPROVED,
        ]);
    }

    public function requestNewReview(): void
    {
        $this->transitionTo(self::STATUS_PENDING, [
            self::STATUS_REJECTED,
        ]);
    }

    private function transitionTo(
        string $target,
        array $allowedOrigins
    ): void {
        if (!in_array($this->statut, $allowedOrigins, true)) {
            throw new \DomainException(sprintf(
                'Transition de statut entreprise non autorisée : %s -> %s.',
                $this->statut,
                $target
            ));
        }

        $this->statut = $target;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}