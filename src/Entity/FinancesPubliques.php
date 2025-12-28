<?php

namespace App\Entity;

use App\Repository\FinancesPubliquesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FinancesPubliquesRepository::class)]
#[ORM\Table(name: 'finances_publiques')]
class FinancesPubliques
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConjonctureJour $conjoncture = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $recettes_totales = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $recettes_fiscales = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $autres_recettes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $depenses_totales = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $solde = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConjoncture(): ?ConjonctureJour
    {
        return $this->conjoncture;
    }

    public function setConjoncture(?ConjonctureJour $conjoncture): static
    {
        $this->conjoncture = $conjoncture;

        return $this;
    }

    public function getRecettesTotales(): ?string
    {
        return $this->recettes_totales;
    }

    public function setRecettesTotales(?string $recettes_totales): static
    {
        $this->recettes_totales = $recettes_totales;

        return $this;
    }

    public function getRecettesFiscales(): ?string
    {
        return $this->recettes_fiscales;
    }

    public function setRecettesFiscales(?string $recettes_fiscales): static
    {
        $this->recettes_fiscales = $recettes_fiscales;

        return $this;
    }

    public function getAutresRecettes(): ?string
    {
        return $this->autres_recettes;
    }

    public function setAutresRecettes(?string $autres_recettes): static
    {
        $this->autres_recettes = $autres_recettes;

        return $this;
    }

    public function getDepensesTotales(): ?string
    {
        return $this->depenses_totales;
    }

    public function setDepensesTotales(?string $depenses_totales): static
    {
        $this->depenses_totales = $depenses_totales;

        return $this;
    }

    public function getSolde(): ?string
    {
        return $this->solde;
    }

    public function setSolde(?string $solde): static
    {
        $this->solde = $solde;

        return $this;
    }
}
