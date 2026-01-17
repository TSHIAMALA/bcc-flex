<?php

namespace App\Entity;

use App\Repository\RegleInterventionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegleInterventionRepository::class)]
#[ORM\Table(name: 'regles_intervention')]
class RegleIntervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Indicateur::class, inversedBy: 'regles')]
    #[ORM\JoinColumn(name: 'indicateur_id', nullable: false)]
    private ?Indicateur $indicateur = null;

    #[ORM\Column(length: 10)]
    private ?string $baseCalcul = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $seuilAlerte = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $seuilIntervention = null;

    #[ORM\Column(length: 10)]
    private ?string $sens = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $poids = null;

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

    public function getBaseCalcul(): ?string
    {
        return $this->baseCalcul;
    }

    public function setBaseCalcul(string $baseCalcul): static
    {
        $this->baseCalcul = $baseCalcul;
        return $this;
    }

    public function getSeuilAlerte(): ?float
    {
        return $this->seuilAlerte !== null ? (float)$this->seuilAlerte : null;
    }

    public function setSeuilAlerte(?string $seuilAlerte): static
    {
        $this->seuilAlerte = $seuilAlerte;
        return $this;
    }

    public function getSeuilIntervention(): ?float
    {
        return $this->seuilIntervention !== null ? (float)$this->seuilIntervention : null;
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

    public function setPoids(?int $poids): static
    {
        $this->poids = $poids;
        return $this;
    }
}
