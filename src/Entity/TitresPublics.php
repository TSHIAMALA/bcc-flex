<?php

namespace App\Entity;

use App\Repository\TitresPublicsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TitresPublicsRepository::class)]
#[ORM\Table(name: 'titres_publics')]
class TitresPublics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConjonctureJour $conjoncture = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $encours_otindex = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $encours_btindex = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $encours_ot_usd = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $encours_bt_usd = null;

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

    public function getEncoursOtindex(): ?string
    {
        return $this->encours_otindex;
    }

    public function setEncoursOtindex(?string $encours_otindex): static
    {
        $this->encours_otindex = $encours_otindex;

        return $this;
    }

    public function getEncoursBtindex(): ?string
    {
        return $this->encours_btindex;
    }

    public function setEncoursBtindex(?string $encours_btindex): static
    {
        $this->encours_btindex = $encours_btindex;

        return $this;
    }

    public function getEncoursOtUsd(): ?string
    {
        return $this->encours_ot_usd;
    }

    public function setEncoursOtUsd(?string $encours_ot_usd): static
    {
        $this->encours_ot_usd = $encours_ot_usd;

        return $this;
    }

    public function getEncoursBtUsd(): ?string
    {
        return $this->encours_bt_usd;
    }

    public function setEncoursBtUsd(?string $encours_bt_usd): static
    {
        $this->encours_bt_usd = $encours_bt_usd;

        return $this;
    }
}
