<?php

namespace App\Entity;

use App\Repository\SocieteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SocieteRepository::class)]
class Societe extends Donateur
{
    #[ORM\Column(length: 200, nullable: true)]
    private ?string $nom_societe = null;

    #[ORM\Column(length: 9, nullable: true)]
    private ?string $siren = null;

    public function getNomSociete(): ?string
    {
        return $this->nom_societe;
    }

    public function setNomSociete(?string $nom_societe): static
    {
        $this->nom_societe = $nom_societe;
        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): static
    {
        $this->siren = $siren;
        return $this;
    }
}
