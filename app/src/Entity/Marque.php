<?php

namespace App\Entity;

use App\Repository\MarqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarqueRepository::class)]
class Marque
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\OneToMany(mappedBy: 'marque', targetEntity: Casque::class)]
    private Collection $casques;

    public function __construct()
    {
        $this->casques = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
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
            $casque->setMarque($this);
        }

        return $this;
    }

    public function removeCasque(Casque $casque): static
    {
        if ($this->casques->removeElement($casque)) {
            if ($casque->getMarque() === $this) {
                $casque->setMarque(null);
            }
        }

        return $this;
    }
}
