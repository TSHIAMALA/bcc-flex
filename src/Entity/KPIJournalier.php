<?php

namespace App\Entity;

use App\Repository\KPIJournalierRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KPIJournalierRepository::class, readOnly: true)]
#[ORM\Table(name: 'v_kpi_journalier')]
class KPIJournalier
{
    #[ORM\Id]
    #[ORM\Column]
    private ?int $conjoncture_id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_situation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_applicable = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $cours_indicatif = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $parallele_vente = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $ecart_indic_parallele = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $reserves_internationales_usd = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $avoirs_externes_usd = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $recettes_totales = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $depenses_totales = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $solde = null;

    public function getConjonctureId(): ?int
    {
        return $this->conjoncture_id;
    }

    public function getDateSituation(): ?\DateTimeInterface
    {
        return $this->date_situation;
    }

    public function getDateApplicable(): ?\DateTimeInterface
    {
        return $this->date_applicable;
    }

    public function getCoursIndicatif(): ?string
    {
        return $this->cours_indicatif;
    }

    public function getParalleleVente(): ?string
    {
        return $this->parallele_vente;
    }

    public function getEcartIndicParallele(): ?string
    {
        return $this->ecart_indic_parallele;
    }

    public function getReservesInternationalesUsd(): ?string
    {
        return $this->reserves_internationales_usd;
    }

    public function getAvoirsExternesUsd(): ?string
    {
        return $this->avoirs_externes_usd;
    }

    public function getRecettesTotales(): ?string
    {
        return $this->recettes_totales;
    }

    public function getDepensesTotales(): ?string
    {
        return $this->depenses_totales;
    }

    public function getSolde(): ?string
    {
        return $this->solde;
    }
}
