<?php

namespace App\Entity;

use App\Repository\TresorerieEtatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TresorerieEtatRepository::class)]
#[ORM\Table(name: 'tresorerie_etat')]
class TresorerieEtat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConjonctureJour $conjoncture = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $solde_avant_fin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $solde_apres_fin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $solde_cumule_annee = null;

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

    public function getSoldeAvantFin(): ?string
    {
        return $this->solde_avant_fin;
    }

    public function setSoldeAvantFin(?string $solde_avant_fin): static
    {
        $this->solde_avant_fin = $solde_avant_fin;

        return $this;
    }

    public function getSoldeApresFin(): ?string
    {
        return $this->solde_apres_fin;
    }

    public function setSoldeApresFin(?string $solde_apres_fin): static
    {
        $this->solde_apres_fin = $solde_apres_fin;

        return $this;
    }

    public function getSoldeCumuleAnnee(): ?string
    {
        return $this->solde_cumule_annee;
    }

    public function setSoldeCumuleAnnee(?string $solde_cumule_annee): static
    {
        $this->solde_cumule_annee = $solde_cumule_annee;

        return $this;
    }
}
