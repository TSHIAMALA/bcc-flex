<?php

namespace App\Entity;

use App\Repository\RegleInterventionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegleInterventionRepository::class)]
#[ORM\Table(name: 'regle_intervention')]
class RegleIntervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Indicateur $indicateur = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private ?string $seuil_alerte = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private ?string $seuil_intervention = null;

    #[ORM\Column(length: 20)]
    private ?string $base_comparaison = null; // 'JOUR', 'HEBDO', 'VALEUR'

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $poids = null;

    #[ORM\Column(length: 2)]
    private ?string $operateur = null; // '>', '<', '>=', '<='

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
        return $this->seuil_alerte;
    }

    public function setSeuilAlerte(string $seuil_alerte): static
    {
        $this->seuil_alerte = $seuil_alerte;

        return $this;
    }

    public function getSeuilIntervention(): ?string
    {
        return $this->seuil_intervention;
    }

    public function setSeuilIntervention(string $seuil_intervention): static
    {
        $this->seuil_intervention = $seuil_intervention;

        return $this;
    }

    public function getBaseComparaison(): ?string
    {
        return $this->base_comparaison;
    }

    public function setBaseComparaison(string $base_comparaison): static
    {
        $this->base_comparaison = $base_comparaison;

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

    public function getOperateur(): ?string
    {
        return $this->operateur;
    }

    public function setOperateur(string $operateur): static
    {
        $this->operateur = $operateur;

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
