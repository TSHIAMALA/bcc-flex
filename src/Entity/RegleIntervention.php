<?php

namespace App\Entity;

use App\Repository\RegleInterventionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegleInterventionRepository::class)]
class RegleIntervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Indicateur::class, inversedBy: 'regles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Indicateur $indicateur = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $seuilAlerte = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $seuilIntervention = null;

    #[ORM\Column(length: 20)]
    private ?string $sens = 'hausse';

    #[ORM\Column]
    private ?int $poids = 10;

    #[ORM\Column]
    private ?bool $actif = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIndicateur(): ?Indicateur
    {
        return $this->indicateur;
    }

    public function setIndicateur(?Indicateur $indicateur): static
    {
        $this->indicateur = $indicateur;
        return $this;
    }

    public function getSeuilAlerte(): ?string
    {
        return $this->seuilAlerte;
    }

    public function setSeuilAlerte(?string $seuilAlerte): static
    {
        $this->seuilAlerte = $seuilAlerte;
        return $this;
    }

    public function getSeuilIntervention(): ?string
    {
        return $this->seuilIntervention;
    }

    public function setSeuilIntervention(?string $seuilIntervention): static
    {
        $this->seuilIntervention = $seuilIntervention;
        return $this;
    }

    public function getSens(): ?string
    {
        return $this->sens;
    }

    public function setSens(string $sens): static
    {
        $this->sens = $sens;
        return $this;
    }

    public function getPoids(): ?int
    {
        return $this->poids;
    }

    public function setPoids(int $poids): static
    {
        $this->poids = $poids;
        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }
}
