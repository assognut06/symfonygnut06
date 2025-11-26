<?php

namespace App\Entity\Value;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Meta
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $createdBy = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $updatedBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->createdAt = isset($data['createdAt']) ? new \DateTimeImmutable($data['createdAt']) : new \DateTimeImmutable();
        $self->updatedAt = isset($data['updatedAt']) ? new \DateTimeImmutable($data['updatedAt']) : null;
        $self->createdBy = $data['createdBy'] ?? null;
        $self->updatedBy = $data['updatedBy'] ?? null;
        return $self;
    }

    // Getters/Setters
    public function getCreatedAt(): ?\DateTimeImmutable 
    { 
        return $this->createdAt; 
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self 
    { 
        $this->createdAt = $createdAt; 
        return $this; 
    }

    public function getUpdatedAt(): ?\DateTimeImmutable 
    { 
        return $this->updatedAt; 
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self 
    { 
        $this->updatedAt = $updatedAt; 
        return $this; 
    }

    public function getCreatedBy(): ?string 
    { 
        return $this->createdBy; 
    }

    public function setCreatedBy(?string $createdBy): self 
    { 
        $this->createdBy = $createdBy; 
        return $this; 
    }

    public function getUpdatedBy(): ?string 
    { 
        return $this->updatedBy; 
    }

    public function setUpdatedBy(?string $updatedBy): self 
    { 
        $this->updatedBy = $updatedBy; 
        return $this; 
    }

    /**
     * Met à jour les métadonnées de modification
     */
    public function touch(?string $updatedBy = null): self
    {
        $this->updatedAt = new \DateTimeImmutable();
        if ($updatedBy !== null) {
            $this->updatedBy = $updatedBy;
        }
        return $this;
    }
}