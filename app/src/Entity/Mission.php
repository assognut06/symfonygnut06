<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\Table(name: 'mission')]
#[ORM\HasLifecycleCallbacks]
class Mission
{
    public const STATUS_DRAFT = 'brouillon';
    public const STATUS_PENDING = 'en_attente';
    public const STATUS_CORRECTION = 'a_corriger';
    public const STATUS_PUBLISHED = 'publiee';
    public const STATUS_CLOSED = 'cloturee';
    public const STATUS_EXPIRED = 'expiree';
    public const STATUS_WITHDRAWN = 'retiree';

    public const MODALITY_REMOTE = 'distance';
    public const MODALITY_ONSITE = 'sur_place';
    public const MODALITY_HYBRID = 'hybride';

    public const BUDGET_HOUR = 'heure';
    public const BUDGET_DAY = 'jour';
    public const BUDGET_FIXED = 'forfait';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CompteEntreprise::class)]
    #[ORM\JoinColumn(
        name: 'compte_entreprise_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private CompteEntreprise $compteEntreprise;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\NotBlank(groups: ['mission_submit'])]
    #[Assert\Length(max: 120, groups: ['Default', 'mission_submit'])]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(groups: ['mission_submit'])]
    #[Assert\Length(max: 3000, groups: ['Default', 'mission_submit'])]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Competence::class)]
    #[ORM\JoinTable(name: 'mission_competence')]
    #[Assert\Count(
        min: 1,
        minMessage: 'Sélectionnez au moins une compétence.',
        groups: ['mission_submit']
    )]
    private Collection $competences;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\NotBlank(groups: ['mission_submit'])]
    #[Assert\Length(max: 120, groups: ['Default', 'mission_submit'])]
    private ?string $domaine = null;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\NotBlank(groups: ['mission_submit'])]
    #[Assert\Length(max: 120, groups: ['Default', 'mission_submit'])]
    private ?string $zone = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(groups: ['mission_submit'])]
    #[Assert\Choice(
        choices: [
            self::MODALITY_REMOTE,
            self::MODALITY_ONSITE,
            self::MODALITY_HYBRID
        ],
        groups: ['Default', 'mission_submit']
    )]
    private ?string $modalite = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateDebutSouhaitee = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100, groups: ['Default', 'mission_submit'])]
    private ?string $dureeEstimee = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\NotNull(
        message: 'La date limite est obligatoire.',
        groups: ['mission_submit']
    )]
    private ?\DateTimeImmutable $dateLimiteReponse = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2,
        nullable: true
    )]
    #[Assert\Positive(groups: ['Default', 'mission_submit'])]
    private ?string $budgetMin = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2,
        nullable: true
    )]
    #[Assert\Positive(groups: ['Default', 'mission_submit'])]
    private ?string $budgetMax = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(
        choices: [
            self::BUDGET_HOUR,
            self::BUDGET_DAY,
            self::BUDGET_FIXED
        ],
        groups: ['Default', 'mission_submit']
    )]
    private ?string $uniteBudget = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_CORRECTION,
        self::STATUS_PUBLISHED,
        self::STATUS_CLOSED,
        self::STATUS_EXPIRED,
        self::STATUS_WITHDRAWN
    ])]
    private string $statut = self::STATUS_DRAFT;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    public function __construct(CompteEntreprise $compteEntreprise)
    {
        $this->compteEntreprise = $compteEntreprise;
        $this->competences = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompteEntreprise(): CompteEntreprise
    {
        return $this->compteEntreprise;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $nouvelleValeur = self::clean($titre);

        if ($this->titre !== $nouvelleValeur) {
            $this->titre = $nouvelleValeur;
            $this->markContentChanged();
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $nouvelleValeur = self::clean($description);

        if ($this->description !== $nouvelleValeur) {
            $this->description = $nouvelleValeur;
            $this->markContentChanged();
        }

        return $this;
    }

    public function getCompetences(): Collection
    {
        return $this->competences;
    }

    public function addCompetence(Competence $competence): self
    {
        if (!$this->competences->contains($competence)) {
            $this->competences->add($competence);
            $this->markContentChanged();
        }

        return $this;
    }

    public function removeCompetence(Competence $competence): self
    {
        if ($this->competences->removeElement($competence)) {
            $this->markContentChanged();
        }

        return $this;
    }

    public function getDomaine(): ?string
    {
        return $this->domaine;
    }

    public function setDomaine(?string $domaine): self
    {
        $nouvelleValeur = self::clean($domaine);

        if ($this->domaine !== $nouvelleValeur) {
            $this->domaine = $nouvelleValeur;
            $this->markContentChanged();
        }

        return $this;
    }

    public function getZone(): ?string
    {
        return $this->zone;
    }

    public function setZone(?string $zone): self
    {
        $nouvelleValeur = self::clean($zone);

        if ($this->zone !== $nouvelleValeur) {
            $this->zone = $nouvelleValeur;
            $this->markContentChanged();
        }

        return $this;
    }

    public function getModalite(): ?string
    {
        return $this->modalite;
    }

    public function setModalite(?string $modalite): self
    {
        $nouvelleValeur = self::clean($modalite);

        if ($this->modalite !== $nouvelleValeur) {
            $this->modalite = $nouvelleValeur;
            $this->markContentChanged();
        }

        return $this;
    }

    public function getDateDebutSouhaitee(): ?\DateTimeImmutable
    {
        return $this->dateDebutSouhaitee;
    }

    public function setDateDebutSouhaitee(?\DateTimeImmutable $date): self {

        if ($this->dateDebutSouhaitee?->format('Y-m-d') !== $date?->format('Y-m-d')) {
            $this->dateDebutSouhaitee = $date;
            $this->markContentChanged();
        }

        return $this;
    }

    public function getDureeEstimee(): ?string
    {
        return $this->dureeEstimee;
    }

    public function setDureeEstimee(?string $duree): self
    {
        $nouvelleValeur = self::clean($duree);

        if ($this->dureeEstimee !== $nouvelleValeur) {
            $this->dureeEstimee = $nouvelleValeur;
            $this->markContentChanged();
        }

        return $this;
    }

    public function getDateLimiteReponse(): ?\DateTimeImmutable
    {
        return $this->dateLimiteReponse;
    }

    public function setDateLimiteReponse(
        ?\DateTimeImmutable $date
    ): self {
        if (
            $this->dateLimiteReponse?->format('Y-m-d')
            !== $date?->format('Y-m-d')
        ) {
            $this->dateLimiteReponse = $date;
            $this->markContentChanged();
        }

        return $this;
    }

    public function getBudgetMin(): ?string
    {
        return $this->budgetMin;
    }

    public function setBudgetMin(?string $montant): self
    {
        $nouvelleValeur = self::clean($montant);

        if ($this->budgetMin !== $nouvelleValeur) {
            $this->budgetMin = $nouvelleValeur;
            $this->markContentChanged();
        }

        return $this;
    }

    public function getBudgetMax(): ?string
    {
        return $this->budgetMax;
    }

    public function setBudgetMax(?string $montant): self
    {
        $nouvelleValeur = self::clean($montant);

        if ($this->budgetMax !== $nouvelleValeur) {
            $this->budgetMax = $nouvelleValeur;
            $this->markContentChanged();
        }

        return $this;
    }

    public function getUniteBudget(): ?string
    {
        return $this->uniteBudget;
    }

    public function setUniteBudget(?string $unite): self
    {
        $nouvelleValeur = self::clean($unite);

        if ($this->uniteBudget !== $nouvelleValeur) {
            $this->uniteBudget = $nouvelleValeur;
            $this->markContentChanged();
        }

        return $this;
    }

    #[Assert\Callback(groups: ['mission_submit'])]
    public function validateSubmission(
        ExecutionContextInterface $context
    ): void {
        $today = new \DateTimeImmutable('today');

        if (
            $this->dateLimiteReponse !== null
            && $this->dateLimiteReponse->format('Y-m-d')
                <= $today->format('Y-m-d')
        ) {
            $context
                ->buildViolation('La date limite doit être future.')
                ->atPath('dateLimiteReponse')
                ->addViolation();
        }

        if (
            $this->dateDebutSouhaitee !== null
            && $this->dateLimiteReponse !== null
            && $this->dateDebutSouhaitee->format('Y-m-d')
                < $this->dateLimiteReponse->format('Y-m-d')
        ) {
            $context
                ->buildViolation(
                    'La date de début souhaitée ne peut pas précéder la date limite de réponse.'
                )
                ->atPath('dateDebutSouhaitee')
                ->addViolation();
        }

        if (($this->budgetMin !== null || $this->budgetMax !== null) && $this->uniteBudget === null) {
            
        $context->buildViolation('Précisez l’unité du tarif (heure, jour ou forfait).')
                ->atPath('uniteBudget')
                ->addViolation();
        }

        if ($this->budgetMin !== null && $this->budgetMax !== null && is_numeric($this->budgetMin) && is_numeric($this->budgetMax) && (float) $this->budgetMin > (float) $this->budgetMax) {
           
        $context->buildViolation('Le tarif minimum ne peut pas dépasser le tarif maximum.')

                ->atPath('budgetMax')
                ->addViolation();
        }
    }

    public function submit(): void
    {
        if (!$this->compteEntreprise->isApproved()) {
            throw new \DomainException(
                'Le compte entreprise doit être validé avant la soumission.'
            );
        }

        $this->transition(self::STATUS_PENDING, [
            self::STATUS_DRAFT,
            self::STATUS_CORRECTION,
            self::STATUS_PENDING
        ]);

        $this->submittedAt = new \DateTimeImmutable();
        $this->publishedAt = null;
    }

    public function publish(): void
    {
        if (!$this->compteEntreprise->isApproved()) {
            throw new \DomainException('Le compte entreprise doit être validé avant la publication.');
        }

        if ($this->dateLimiteReponse === null || $this->dateLimiteReponse->format('Y-m-d') <= (new \DateTimeImmutable('today'))->format('Y-m-d')) {
            
        throw new \DomainException('Impossible de publier une mission dont la date limite est échue.');
        }

        $this->transition(
            self::STATUS_PUBLISHED,
            [self::STATUS_PENDING]
        );

        $this->publishedAt = new \DateTimeImmutable();
    }

    public function requestCorrection(): void
    {
        $this->transition(
            self::STATUS_CORRECTION,
            [self::STATUS_PENDING]
        );
    }

    public function close(): void
    {
        $this->transition(
            self::STATUS_CLOSED,
            [self::STATUS_PUBLISHED]
        );
    }

    public function expire(): void
    {
        if (
            $this->dateLimiteReponse === null
            || $this->dateLimiteReponse->format('Y-m-d')
                >= (new \DateTimeImmutable('today'))->format('Y-m-d')
        ) {
            throw new \DomainException(
                'La mission ne peut expirer qu’après sa date limite de réponse.'
            );
        }

        $this->transition(
            self::STATUS_EXPIRED,
            [self::STATUS_PUBLISHED]
        );
    }

    public function withdraw(): void
    {
        $this->transition(
            self::STATUS_WITHDRAWN,
            [self::STATUS_PUBLISHED]
        );
    }

    public function reopen(\DateTimeImmutable $newDeadline): void
    {
        if (!$this->compteEntreprise->isApproved()) {
            throw new \DomainException(
                'Le compte entreprise doit être validé avant la réouverture.'
            );
        }

        if (!in_array(
            $this->statut,
            [self::STATUS_CLOSED, self::STATUS_EXPIRED],
            true
        )) {
            throw new \DomainException(
                'Seule une mission clôturée ou expirée peut être rouverte.'
            );
        }

        if (
            $newDeadline->format('Y-m-d')
            <= (new \DateTimeImmutable('today'))->format('Y-m-d')
        ) {
            throw new \DomainException(
                'La nouvelle date limite doit être future.'
            );
        }

        if (
            $this->dateLimiteReponse?->format('Y-m-d')
            === $newDeadline->format('Y-m-d')
        ) {
            throw new \DomainException(
                'La réouverture exige une nouvelle date limite de réponse.'
            );
        }

        $this->setDateLimiteReponse($newDeadline);

        $this->transition(
            self::STATUS_PENDING,
            [self::STATUS_CLOSED, self::STATUS_EXPIRED]
        );

        $this->submittedAt = new \DateTimeImmutable();
        $this->publishedAt = null;
    }

    public function isPubliclyVisible(
        ?\DateTimeImmutable $now = null
    ): bool {
        $now ??= new \DateTimeImmutable();

        return $this->statut === self::STATUS_PUBLISHED
            && $this->compteEntreprise->isApproved()
            && $this->dateLimiteReponse !== null
            && $this->dateLimiteReponse->format('Y-m-d')
                >= $now->format('Y-m-d');
    }

    private function transition(
        string $target,
        array $allowedSources
    ): void {
        if (!in_array(
            $this->statut,
            $allowedSources,
            true
        )) {
            throw new \DomainException(sprintf(
                'Transition mission interdite : %s -> %s',
                $this->statut,
                $target
            ));
        }

        $this->statut = $target;
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function markContentChanged(): void
    {
        if ($this->statut === self::STATUS_PUBLISHED) {
            $this->statut = self::STATUS_PENDING;
            $this->submittedAt = new \DateTimeImmutable();
            $this->publishedAt = null;
        }

        $this->updatedAt = new \DateTimeImmutable();
    }

    private static function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}