<?php

namespace App\Entity;

use App\Entity\Value\Meta;
use App\Repository\AssoRecommanderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssoRecommanderRepository::class)]
#[ORM\Table(name: 'asso_recommander')]
#[ORM\Index(columns: ['name'], name: 'idx_name')]
#[ORM\Index(columns: ['city'], name: 'idx_city')]
#[ORM\Index(columns: ['zip_code'], name: 'idx_zip_code')]
#[ORM\Index(columns: ['category'], name: 'idx_category')]
#[ORM\Index(columns: ['type'], name: 'idx_type')]
#[ORM\UniqueConstraint(name: 'unique_organization_slug', columns: ['organization_slug'])]
#[ORM\HasLifecycleCallbacks]
class AssoRecommander
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 191)]
    private ?string $organizationSlug = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $banner = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $fiscalReceiptEligibility = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $fiscalReceiptIssuanceEnabled = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(type: Types::STRING, length: 191, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Embedded(class: Meta::class)]
    private Meta $meta;

    public function __construct()
    {
        $this->meta = new Meta();
        $this->createdAt = new \DateTimeImmutable();
    }

    // ✅ TOUS les getters/setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganizationSlug(): ?string
    {
        return $this->organizationSlug;
    }

    public function setOrganizationSlug(string $organizationSlug): static
    {
        $this->organizationSlug = $organizationSlug;
        return $this;
    }

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(?string $banner): static
    {
        $this->banner = $banner;
        return $this;
    }

    public function isFiscalReceiptEligibility(): ?bool
    {
        return $this->fiscalReceiptEligibility;
    }

    public function setFiscalReceiptEligibility(?bool $fiscalReceiptEligibility): static
    {
        $this->fiscalReceiptEligibility = $fiscalReceiptEligibility;
        return $this;
    }

    public function isFiscalReceiptIssuanceEnabled(): ?bool
    {
        return $this->fiscalReceiptIssuanceEnabled;
    }

    public function setFiscalReceiptIssuanceEnabled(?bool $fiscalReceiptIssuanceEnabled): static
    {
        $this->fiscalReceiptIssuanceEnabled = $fiscalReceiptIssuanceEnabled;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getMeta(): Meta
    {
        return $this->meta;
    }

    public function setMeta(Meta $meta): static
    {
        $this->meta = $meta;
        return $this;
    }

    /**
     * Remplit l'entité avec les données de l'API HelloAsso
     */
    public function fillFromApiData(array $data): self
    {
        $this->banner = $this->truncateString($data['banner'] ?? null, 500);
        $this->fiscalReceiptEligibility = $data['fiscalReceiptEligibility'] ?? null;
        $this->fiscalReceiptIssuanceEnabled = $data['fiscalReceiptIssuanceEnabled'] ?? null;
        $this->type = $this->truncateString($data['type'] ?? null, 100);
        $this->category = $this->truncateString($data['category'] ?? null, 100);
        $this->logo = $this->truncateString($data['logo'] ?? null, 500);
        $this->name = $this->truncateString($data['name'] ?? null, 191);
        $this->city = $this->truncateString($data['city'] ?? null, 100);
        $this->zipCode = $this->truncateString($data['zipCode'] ?? null, 10);
        $this->description = $data['description'] ?? null;
        $this->url = $this->truncateString($data['url'] ?? null, 500);
        
        // Mettre à jour les métadonnées
        $this->meta->touch();
        
        return $this;
    }

    /**
     * Met à jour automatiquement la date de modification
     */
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
        $this->meta->touch();
    }

    /**
     * Tronque une chaîne si elle dépasse la longueur maximale
     */
    private function truncateString(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > $maxLength 
            ? mb_substr($value, 0, $maxLength) 
            : $value;
    }

    /**
     * Retourne une représentation string de l'entité
     */
    public function __toString(): string
    {
        return $this->name ?? $this->organizationSlug ?? 'Association #' . $this->id;
    }
}