<?php

namespace App\Entity;

use App\Repository\HelloAssoTierCustomFieldRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: HelloAssoTierCustomFieldRepository::class)]
#[ORM\Table(name: 'helloasso_tier_custom_field')]
#[ORM\Index(name: 'idx_cf_external', columns: ['external_id'])]
#[ORM\UniqueConstraint(name: 'uniq_cf_tier_external', columns: ['tier_id', 'external_id'])]
class HelloAssoTierCustomField
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: HelloAssoTier::class, inversedBy: 'customFields')]
    #[ORM\JoinColumn(name: 'tier_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?HelloAssoTier $tier = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $externalId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isRequired = false;

    // ❌ PROBLÈME : 'values' est un mot-clé réservé MySQL
    // #[ORM\Column(type: Types::JSON, nullable: true)]
    // private ?array $values = null;

    // ✅ SOLUTION : Échapper avec backticks
    /**
     * @var ?array<mixed>
     */
    #[ORM\Column(name: '`values`', type: Types::JSON, nullable: true)]
    private ?array $values = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    /**
     * ✅ MÉTHODE STATIQUE : Créer un champ personnalisé depuis des données d'API
     * 
     * @param array{id:?int,label:?string,type:?string,isRequired:?bool,values:?array<mixed>} $data
     */

    public static function fromArray(array $data): self
    {
        $field = new self();
        
        $field->setExternalId($data['id'] ?? null);
        $field->setLabel($data['label'] ?? null);
        $field->setType($data['type'] ?? null);
        $field->setIsRequired($data['isRequired'] ?? false);
        $field->setValues($data['values'] ?? null);

        return $field;
    }

    // ✅ GETTERS ET SETTERS
    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTier(): ?HelloAssoTier
    {
        return $this->tier;
    }

    public function setTier(?HelloAssoTier $tier): self
    {
        $this->tier = $tier;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getIsRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): self
    {
        $this->isRequired = $isRequired;
        return $this;
    }
    /**
     * @return array<mixed>
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    /**
     * @param array<mixed> $values
     */
    public function setValues(?array $values): self
    {
        $this->values = $values;
        return $this;
    }

    /**
     * ✅ MÉTHODES UTILITAIRES
     */
    public function hasOptions(): bool
    {
        return !($this->values)&&!empty($this->values);
    }

    public function getOptionsAsString(): string
    {
        if ($this->hasOptions()) {
            return implode(', ', $this->values);
        }
        return '';
    }

    public function isSelectType(): bool
    {
        return in_array($this->type, ['select', 'radio', 'checkbox']);
    }

    public function isTextType(): bool
    {
        return in_array($this->type, ['text', 'textarea', 'email', 'phone']);
    }

    public function __toString(): string
    {
        return $this->label ?? 'Custom Field #' . ($this->externalId ??  $this->id);
    }
}