<?php

namespace App\Entity;

use App\Repository\IndicateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: IndicateurRepository::class)]
#[ORM\Table(name: 'indicateurs')]
class Indicateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $unite = null;

    #[ORM\OneToMany(mappedBy: 'indicateur', targetEntity: RegleIntervention::class)]
    private Collection $regles;

    #[ORM\OneToMany(mappedBy: 'indicateur', targetEntity: AlerteChange::class)]
    private Collection $alertes;

    public function __construct()
    {
        $this->regles = new ArrayCollection();
        $this->alertes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(?string $unite): static
    {
        $this->unite = $unite;
        return $this;
    }

    public function getRegles(): Collection
    {
        return $this->regles;
    }

    public function getAlertes(): Collection
    {
        return $this->alertes;
    }
}
