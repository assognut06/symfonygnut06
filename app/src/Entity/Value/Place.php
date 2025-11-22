<?php

namespace App\Entity\Value;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Place
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $country = null; // ex: FRA

    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->address = $data['address'] ?? null;
        $self->name = $data['name'] ?? null;
        $self->city = $data['city'] ?? null;
        $self->zipCode = $data['zipCode'] ?? null;
        $self->country = $data['country'] ?? null;
        return $self;
    }

    // Getters/Setters
    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): self { $this->address = $address; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): self { $this->city = $city; return $this; }

    public function getZipCode(): ?string { return $this->zipCode; }
    public function setZipCode(?string $zipCode): self { $this->zipCode = $zipCode; return $this; }

    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $country): self { $this->country = $country; return $this; }
}
