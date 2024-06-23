<?php

namespace App\Entity;

use App\Repository\AssoRecommanderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssoRecommanderRepository::class)]
class AssoRecommander
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $organizationSlug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $banner = null;

    #[ORM\Column(nullable: true)]
    private ?bool $fiscalReceiptEligibility = null;

    #[ORM\Column(nullable: true)]
    private ?bool $fiscalReceiptIssuanceEnabled = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $CreatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $UpdatedAt = null;

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
        return $this->CreatedAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $CreatedAt): static
    {
        $this->CreatedAt = $CreatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->UpdatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $UpdatedAt): static
    {
        $this->UpdatedAt = $UpdatedAt;

        return $this;
    }

    public function fillFromApiData(array $data): self
    {
        $this->banner = $data['banner'] ?? null;
        $this->fiscalReceiptEligibility = $data['fiscalReceiptEligibility'] ?? null;
        $this->fiscalReceiptIssuanceEnabled = $data['fiscalReceiptIssuanceEnabled'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->category = $data['category'] ?? null;
        $this->logo = $data['logo'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->city = $data['city'] ?? null;
        $this->zipCode = $data['zipCode'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->url = $data['url'] ?? null; 
        
        return $this;
    }
}
