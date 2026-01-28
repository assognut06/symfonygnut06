<?php

namespace App\Entity;

use App\Repository\TihRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TihRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Tih
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'tih', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $professionalEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $departement = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $availability = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $availabilityDate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $rate = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $rateType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attestationTih = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTimeInterface $updatedAt;

    #[ORM\ManyToMany(targetEntity: Competence::class)]
    private Collection $competences;

    #[ORM\Column(type: 'boolean')]
    private bool $isValidate = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $validationMessage = null;

    public function __construct()
    {
        $this->competences = new ArrayCollection();
        $this->isValidate = false;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): self { $this->title = $title; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): self { $this->lastName = $lastName; return $this; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): self { $this->firstName = $firstName; return $this; }

    public function getProfessionalEmail(): ?string { return $this->professionalEmail; }
    public function setProfessionalEmail(?string $professionalEmail): self { $this->professionalEmail = $professionalEmail; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): self { $this->address = $address; return $this; }

    public function getPostalCode(): ?string { return $this->postalCode; }
    public function setPostalCode(?string $postalCode): self { $this->postalCode = $postalCode; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): self { $this->city = $city; return $this; }

    public function getRegion(): ?string { return $this->region; }
    public function setRegion(?string $region): self { $this->region = $region; return $this; }

    public function getDepartement(): ?string { return $this->departement; }
    public function setDepartement(?string $departement): self { $this->departement = $departement; return $this; }

    public function getAvailability(): ?string { return $this->availability; }
    public function setAvailability(?string $availability): self { $this->availability = $availability; return $this; }

    public function getAvailabilityDate(): ?\DateTimeInterface { return $this->availabilityDate; }
    public function setAvailabilityDate(?\DateTimeInterface $availabilityDate): self { $this->availabilityDate = $availabilityDate; return $this; }

    public function getRate(): ?string { return $this->rate; }
    public function setRate(?string $rate): self { $this->rate = $rate; return $this; }

    public function getRateType(): ?string { return $this->rateType; }
    public function setRateType(?string $rateType): self { $this->rateType = $rateType; return $this; }

    public function getCv(): ?string { return $this->cv; }
    public function setCv(?string $cv): self { $this->cv = $cv; return $this; }

    public function getSiret(): ?string { return $this->siret; }
    public function setSiret(?string $siret): self { $this->siret = $siret; return $this; }

    public function getAttestationTih(): ?string { return $this->attestationTih; }
    public function setAttestationTih(?string $attestationTih): self { $this->attestationTih = $attestationTih; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    /** @return Collection<int, Competence> */
    public function getCompetences(): Collection { return $this->competences; }
    public function addCompetence(Competence $competence): self
    {
        if (!$this->competences->contains($competence)) {
            $this->competences->add($competence);
        }
        return $this;
    }
    public function removeCompetence(Competence $competence): self
    {
        $this->competences->removeElement($competence);
        return $this;
    }

    public function isValidate(): bool { return $this->isValidate; }
    public function setIsValidate(bool $isValidate): self { $this->isValidate = $isValidate; return $this; }

    public function getValidationMessage(): ?string { return $this->validationMessage; }
    public function setValidationMessage(?string $validationMessage): self { $this->validationMessage = $validationMessage; return $this; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): self { $this->photo = $photo; return $this; }
}
