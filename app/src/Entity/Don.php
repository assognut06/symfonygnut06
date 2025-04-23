<?php

namespace App\Entity;

use App\Repository\DonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Donateur;

#[ORM\Entity(repositoryClass: DonRepository::class)]
class Don
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_creation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_mise_a_jour = null;

    #[ORM\Column(length: 30)]
    private ?string $statut = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $message = null;

    #[ORM\ManyToOne(targetEntity: Donateur::class, inversedBy: 'dons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Donateur $donateur = null;

    #[ORM\OneToMany(mappedBy: 'don', targetEntity: Casque::class, cascade: ['persist', 'remove'])]
    private Collection $casques;

    #[ORM\ManyToOne(targetEntity: ModeLivraison::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?ModeLivraison $modeLivraison = null;

    #[ORM\ManyToOne(targetEntity: PartenaireLogistique::class)]
    #[ORM\JoinColumn(nullable: true)] // NULL si le mode est "Dépôt"
    private ?PartenaireLogistique $partenaireLogistique = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $numero_suivi = null;

    public function __construct()
    {
        $this->casques = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeImmutable $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getDateMiseAJour(): ?\DateTimeInterface
    {
        return $this->date_mise_a_jour;
    }

    public function setDateMiseAJour(\DateTimeInterface $date_mise_a_jour): static
    {
        $this->date_mise_a_jour = $date_mise_a_jour;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getDonateur(): ?Donateur
    {
        return $this->donateur;
    }

    public function setDonateur(?Donateur $donateur): static
    {
        $this->donateur = $donateur;
        return $this;
    }

    /**
     * @return Collection<int, Casque>
     */
    public function getCasques(): Collection
    {
        return $this->casques;
    }

    public function addCasque(Casque $casque): static
    {
        if (!$this->casques->contains($casque)) {
            $this->casques->add($casque);
            $casque->setDon($this);
        }
        return $this;
    }

    public function removeCasque(Casque $casque): static
    {
        if ($this->casques->removeElement($casque)) {
            if ($casque->getDon() === $this) {
                $casque->setDon(null);
            }
        }
        return $this;
    }

    public function getModeLivraison(): ?ModeLivraison
    {
        return $this->modeLivraison;
    }

    public function setModeLivraison(?ModeLivraison $modeLivraison): static
    {
        $this->modeLivraison = $modeLivraison;
        return $this;
    }

    public function getPartenaireLogistique(): ?PartenaireLogistique
    {
        return $this->partenaireLogistique;
    }

    public function setPartenaireLogistique(?PartenaireLogistique $partenaireLogistique): static
    {
        $this->partenaireLogistique = $partenaireLogistique;
        return $this;
    }

    public function getNumeroSuivi(): ?string
    {
        return $this->numero_suivi;
    }

    public function setNumeroSuivi(?string $numero_suivi): static
    {
        $this->numero_suivi = $numero_suivi;
        return $this;
    }
}