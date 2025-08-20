<?php

namespace App\Entity;

use App\Repository\TihRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TihRepository::class)]
class Tih
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'tih', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $civilite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailPro = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $disponibilite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attestationTih = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateCreation;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateMiseAJour;

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

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getCivilite(): ?string { return $this->civilite; }
    public function setCivilite(?string $civilite): self { $this->civilite = $civilite; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(?string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getEmailPro(): ?string { return $this->emailPro; }
    public function setEmailPro(?string $emailPro): self { $this->emailPro = $emailPro; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): self { $this->telephone = $telephone; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): self { $this->adresse = $adresse; return $this; }

    public function getCodePostal(): ?string { return $this->codePostal; }
    public function setCodePostal(?string $codePostal): self { $this->codePostal = $codePostal; return $this; }

    public function getVille(): ?string { return $this->ville; }
    public function setVille(?string $ville): self { $this->ville = $ville; return $this; }

    public function getDisponibilite(): ?string { return $this->disponibilite; }
    public function setDisponibilite(?string $disponibilite): self { $this->disponibilite = $disponibilite; return $this; }

    public function getCv(): ?string { return $this->cv; }
    public function setCv(?string $cv): self { $this->cv = $cv; return $this; }

    public function getSiret(): ?string { return $this->siret; }
    public function setSiret(?string $siret): self { $this->siret = $siret; return $this; }

    public function getAttestationTih(): ?string { return $this->attestationTih; }
    public function setAttestationTih(?string $attestationTih): self { $this->attestationTih = $attestationTih; return $this; }

    public function getDateCreation(): \DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): self { $this->dateCreation = $dateCreation; return $this; }

    public function getDateMiseAJour(): \DateTimeInterface { return $this->dateMiseAJour; }
    public function setDateMiseAJour(\DateTimeInterface $dateMiseAJour): self { $this->dateMiseAJour = $dateMiseAJour; return $this; }

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
}
