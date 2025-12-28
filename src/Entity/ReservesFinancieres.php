<?php

namespace App\Entity;

use App\Repository\ReservesFinancieresRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservesFinancieresRepository::class)]
#[ORM\Table(name: 'reserves_financieres')]
class ReservesFinancieres
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConjonctureJour $conjoncture = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $reserves_internationales_usd = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $avoirs_externes_usd = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $reserves_banques_cdf = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $avoirs_libres_cdf = null;

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

    public function getReservesInternationalesUsd(): ?string
    {
        return $this->reserves_internationales_usd;
    }

    public function setReservesInternationalesUsd(?string $reserves_internationales_usd): static
    {
        $this->reserves_internationales_usd = $reserves_internationales_usd;

        return $this;
    }

    public function getAvoirsExternesUsd(): ?string
    {
        return $this->avoirs_externes_usd;
    }

    public function setAvoirsExternesUsd(?string $avoirs_externes_usd): static
    {
        $this->avoirs_externes_usd = $avoirs_externes_usd;

        return $this;
    }

    public function getReservesBanquesCdf(): ?string
    {
        return $this->reserves_banques_cdf;
    }

    public function setReservesBanquesCdf(?string $reserves_banques_cdf): static
    {
        $this->reserves_banques_cdf = $reserves_banques_cdf;

        return $this;
    }

    public function getAvoirsLibresCdf(): ?string
    {
        return $this->avoirs_libres_cdf;
    }

    public function setAvoirsLibresCdf(?string $avoirs_libres_cdf): static
    {
        $this->avoirs_libres_cdf = $avoirs_libres_cdf;

        return $this;
    }
}
