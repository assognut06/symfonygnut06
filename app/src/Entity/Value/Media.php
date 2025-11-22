<?php

namespace App\Entity\Value;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Media
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $publicUrl = null;

    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->fileName = $data['fileName'] ?? null;
        $self->publicUrl = $data['publicUrl'] ?? null;
        return $self;
    }

    // Getters/Setters
    public function getFileName(): ?string { return $this->fileName; }
    public function setFileName(?string $fileName): self { $this->fileName = $fileName; return $this; }

    public function getPublicUrl(): ?string { return $this->publicUrl; }
    public function setPublicUrl(?string $publicUrl): self { $this->publicUrl = $publicUrl; return $this; }
}