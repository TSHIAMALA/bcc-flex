<?php

namespace App\Entity;

use App\Repository\EncoursBccRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EncoursBccRepository::class)]
#[ORM\Table(name: 'encours_bcc')]
class EncoursBcc
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConjonctureJour $conjoncture = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $encours_ot_bcc = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $encours_b_bcc = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    private ?string $taux_interbancaire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    private ?string $taux_moyen_pondere_bbcc = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $billets_en_circulation = null;

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

    public function getEncoursOtBcc(): ?string
    {
        return $this->encours_ot_bcc;
    }

    public function setEncoursOtBcc(?string $encours_ot_bcc): static
    {
        $this->encours_ot_bcc = $encours_ot_bcc;

        return $this;
    }

    public function getEncoursBBcc(): ?string
    {
        return $this->encours_b_bcc;
    }

    public function setEncoursBBcc(?string $encours_b_bcc): static
    {
        $this->encours_b_bcc = $encours_b_bcc;

        return $this;
    }

    public function getTauxInterbancaire(): ?string
    {
        return $this->taux_interbancaire;
    }

    public function setTauxInterbancaire(?string $taux_interbancaire): static
    {
        $this->taux_interbancaire = $taux_interbancaire;

        return $this;
    }

    public function getTauxMoyenPondereBbcc(): ?string
    {
        return $this->taux_moyen_pondere_bbcc;
    }

    public function setTauxMoyenPondereBbcc(?string $taux_moyen_pondere_bbcc): static
    {
        $this->taux_moyen_pondere_bbcc = $taux_moyen_pondere_bbcc;

        return $this;
    }

    public function getBilletsEnCirculation(): ?string
    {
        return $this->billets_en_circulation;
    }

    public function setBilletsEnCirculation(?string $billets_en_circulation): static
    {
        $this->billets_en_circulation = $billets_en_circulation;

        return $this;
    }
}
