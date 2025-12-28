<?php

namespace App\Entity;

use App\Repository\PaieEtatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaieEtatRepository::class)]
#[ORM\Table(name: 'paie_etat')]
class PaieEtat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConjonctureJour $conjoncture = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $montant_total = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $montant_paye = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $montant_restant = null;

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

    public function getMontantTotal(): ?string
    {
        return $this->montant_total;
    }

    public function setMontantTotal(?string $montant_total): static
    {
        $this->montant_total = $montant_total;

        return $this;
    }

    public function getMontantPaye(): ?string
    {
        return $this->montant_paye;
    }

    public function setMontantPaye(?string $montant_paye): static
    {
        $this->montant_paye = $montant_paye;

        return $this;
    }

    public function getMontantRestant(): ?string
    {
        return $this->montant_restant;
    }

    public function setMontantRestant(?string $montant_restant): static
    {
        $this->montant_restant = $montant_restant;

        return $this;
    }
}
