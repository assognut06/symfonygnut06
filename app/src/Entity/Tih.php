<?php

namespace App\Entity;

use App\Repository\TihRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\EntrepriseTihMessage;


#[ORM\Entity(repositoryClass: TihRepository::class)]
class Tih
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $civilite = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $disponibilite = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateMiseAJour = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $emailPro = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $competences = null;

    #[ORM\OneToMany(mappedBy: 'tih', targetEntity: EntrepriseTihMessage::class, cascade: ['persist', 'remove'])]
    private Collection $messages;

    #[ORM\OneToOne(inversedBy: 'tih', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?User $user = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCivilite(): ?string 
    { 
        return $this->civilite; 
    }

    public function setCivilite(?string $civilite): static 
    { 
        $this->civilite = $civilite; return $this; 
    }

    public function getDisponibilite(): ?string 
    { 
        return $this->disponibilite; 
    }

    public function setDisponibilite(?string $disponibilite): static 
    { 
        $this->disponibilite = $disponibilite; return $this; 
    }

    public function getDateCreation(): ?\DateTimeInterface 
    { 
        return $this->dateCreation; 
    }
    public function setDateCreation(\DateTimeInterface $date): static 
    { 
        $this->dateCreation = $date; return $this; 
    }

    public function getDateMiseAJour(): ?\DateTimeInterface 
    { 
        return $this->dateMiseAJour; 
    }

    public function setDateMiseAJour(\DateTimeInterface $date): static 
    { 
        $this->dateMiseAJour = $date; return $this; 
    }

    public function getNom(): ?string 
    { 
        return $this->nom; 
    }

    public function setNom(?string $nom): static 
    { 
        $this->nom = $nom; return $this; 
    }

    public function getPrenom(): ?string 
    { 
        return $this->prenom; 
    }

    public function setPrenom(?string $prenom): static 
    { 
        $this->prenom = $prenom; return $this; 
    }

    public function getEmailPro(): ?string 
    { 
        return $this->emailPro; 
    }

    public function setEmailPro(?string $email): static 
    { 
        $this->emailPro = $email; return $this; 
    }

    public function getTelephone(): ?string 
    { 
        return $this->telephone; 
    }

    public function setTelephone(?string $tel): static 
    { 
        $this->telephone = $tel; return $this; 
    }

    public function getAdresse(): ?string 
    { 
        return $this->adresse; 
    }
    public function setAdresse(?string $adresse): static 
    { 
        $this->adresse = $adresse; return $this; 
    }

    public function getCodePostal(): ?string 
    { 
        return $this->codePostal; 
    }

    public function setCodePostal(?string $cp): static 
    { 
        $this->codePostal = $cp; return $this; 
    }

    public function getVille(): ?string 
    { 
        return $this->ville; 
    }

    public function setVille(?string $ville): static 
    { 
        $this->ville = $ville; return $this; 
    }

    public function getCv(): ?string 
    { 
        return $this->cv; 
    }

    public function setCv(?string $cv): static 
    { 
        $this->cv = $cv; return $this; 
    }

    public function getCompetences(): ?string 
    { 
        return $this->competences; 
    }

    public function setCompetences(?string $competences): static 
    { 
        $this->competences = $competences; return $this; 
    }

    public function getUser(): ?User 
    { 
        return $this->user; 
    }

    public function setUser(User $user): static 
    { 
        $this->user = $user; return $this; 
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(EntrepriseTihMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setTih($this);
        }

        return $this;
    }

    public function removeMessage(EntrepriseTihMessage $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getTih() === $this) {
                $message->setTih(null);
            }
        }

        return $this;
    }
}
