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
    #[ORM\Column(type: 'string', length: 10)]
    private ?string $date_situation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $cours_indicatif = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $ecart_indic_parallele = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $reserves_internationales_usd = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $solde = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $parallele_vente = null;

    public function getDateSituation(): ?string
    {
        return $this->date_situation;
    }

    public function getCoursIndicatif(): ?string
    {
        return $this->cours_indicatif;
    }

    public function getEcartIndicParallele(): ?string
    {
        return $this->ecart_indic_parallele;
    }

    public function getReservesInternationalesUsd(): ?string
    {
        return $this->reserves_internationales_usd;
    }

    public function getSolde(): ?string
    {
        return $this->solde;
    }

    public function getParalleleVente(): ?string
    {
        return $this->parallele_vente;
    }
}
