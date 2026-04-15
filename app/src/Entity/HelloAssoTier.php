<?php

namespace App\Entity;

use App\Repository\HelloAssoTierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: HelloAssoTierRepository::class)]
#[ORM\Table(name: 'helloasso_tier')]
#[ORM\Index(name: 'idx_tier_external', columns: ['external_id'])]
#[ORM\UniqueConstraint(name: 'uniq_tier_form_external', columns: ['form_id', 'external_id'])]
class HelloAssoTier
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: HelloAssoFormNotification::class, inversedBy: 'tiers')]
    #[ORM\JoinColumn(name: 'form_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?HelloAssoFormNotification $form = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $externalId = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $label = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $tierType = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $vatRate = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $paymentFrequency = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $isEligibleTaxReceipt = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $isFavorite = null;

    #[ORM\OneToMany(mappedBy: 'tier', targetEntity: HelloAssoTierCustomField::class, cascade: ['persist', 'remove'])]
    private Collection $customFields;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->customFields = new ArrayCollection();
    }

    /**
     * ✅ MÉTHODE STATIQUE : Créer un tier depuis des données d'API
     */
    public static function fromArray(array $data): self
    {
        $tier = new self();
        
        $tier->setExternalId($data['id'] ?? null);
        $tier->setLabel($data['label'] ?? null);
        $tier->setDescription($data['description'] ?? null);
        $tier->setTierType($data['tierType'] ?? null);
        
        // ✅ CORRECTION : Convertir correctement les prix en string
        $tier->setPrice(isset($data['price']) ? (string)$data['price'] : '0.00');
        $tier->setVatRate(isset($data['vatRate']) ? (string)$data['vatRate'] : '0.00');
        
        $tier->setPaymentFrequency($data['paymentFrequency'] ?? null);
        $tier->setIsEligibleTaxReceipt($data['isEligibleTaxReceipt'] ?? null);
        $tier->setIsFavorite($data['isFavorite'] ?? null);

        // Traiter les champs personnalisés si présents
        if (isset($data['customFields']) && is_array($data['customFields'])) {
            foreach ($data['customFields'] as $fieldData) {
                $customField = HelloAssoTierCustomField::fromArray($fieldData);
                $tier->addCustomField($customField);
            }
        }

        return $tier;
    }

    // ✅ GETTERS ET SETTERS
    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getForm(): ?HelloAssoFormNotification
    {
        return $this->form;
    }

    public function setForm(?HelloAssoFormNotification $form): self
    {
        $this->form = $form;
        return $this;
    }

    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(?int $externalId): self
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getTierType(): ?string
    {
        return $this->tierType;
    }

    public function setTierType(?string $tierType): self
    {
        $this->tierType = $tierType;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    // ✅ CORRECTION : Méthode setPrice corrigée
    public function setPrice(mixed $price): self
    {
        if ($price === null) {
            $this->price = null;
        } else {
            // Convertir en string pour DECIMAL
            $this->price = (string)$price;
        }
        return $this;
    }

    public function getVatRate(): ?string
    {
        return $this->vatRate;
    }

    // ✅ CORRECTION : Méthode setVatRate corrigée
    public function setVatRate(mixed $vatRate): self
    {
        if ($vatRate === null) {
            $this->vatRate = null;
        } else {
            // Convertir en string pour DECIMAL
            $this->vatRate = (string)$vatRate;
        }
        return $this;
    }

    public function getPaymentFrequency(): ?string
    {
        return $this->paymentFrequency;
    }

    public function setPaymentFrequency(?string $paymentFrequency): self
    {
        $this->paymentFrequency = $paymentFrequency;
        return $this;
    }

    public function getIsEligibleTaxReceipt(): ?bool
    {
        return $this->isEligibleTaxReceipt;
    }

    public function setIsEligibleTaxReceipt(?bool $isEligibleTaxReceipt): self
    {
        $this->isEligibleTaxReceipt = $isEligibleTaxReceipt;
        return $this;
    }

    public function getIsFavorite(): ?bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(?bool $isFavorite): self
    {
        $this->isFavorite = $isFavorite;
        return $this;
    }

    /**
     * @return Collection<int, HelloAssoTierCustomField>
     */
    public function getCustomFields(): Collection
    {
        return $this->customFields;
    }

    public function addCustomField(HelloAssoTierCustomField $customField): self
    {
        if (!$this->customFields->contains($customField)) {
            $this->customFields->add($customField);
            $customField->setTier($this);
        }

        return $this;
    }

    public function removeCustomField(HelloAssoTierCustomField $customField): self
    {
        if ($this->customFields->removeElement($customField)) {
            if ($customField->getTier() === $this) {
                $customField->setTier(null);
            }
        }

        return $this;
    }

    // ✅ MÉTHODES UTILITAIRES SUPPLÉMENTAIRES
    public function getPriceAsFloat(): ?float
    {
        return $this->price ? (float)$this->price : null;
    }

    public function getVatRateAsFloat(): ?float
    {
        return $this->vatRate ? (float)$this->vatRate : null;
    }

    public function getPriceWithVat(): ?float
    {
        if ($this->price === null) {
            return null;
        }
        
        $price = (float)$this->price;
        $vatRate = $this->vatRate ? (float)$this->vatRate : 0;
        
        return $price * (1 + $vatRate / 100);
    }

    public function getFormattedPrice(): string
    {
        if ($this->price === null) {
            return '0,00 €';
        }
        
        return number_format((float)$this->price, 2, ',', ' ') . ' €';
    }

    public function __toString(): string
    {
        return $this->label ?? 'Tier #' . ($this->externalId ?? $this->id);
    }
}