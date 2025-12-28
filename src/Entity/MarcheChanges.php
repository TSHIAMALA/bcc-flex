<?php

namespace App\Entity;

use App\Repository\MarcheChangesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarcheChangesRepository::class)]
#[ORM\Table(name: 'marche_changes')]
class MarcheChanges
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConjonctureJour $conjoncture = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $cours_indicatif = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $parallele_achat = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $parallele_vente = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $ecart_indic_parallele = null;

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

    public function getCoursIndicatif(): ?string
    {
        return $this->cours_indicatif;
    }

    public function setCoursIndicatif(?string $cours_indicatif): static
    {
        $this->cours_indicatif = $cours_indicatif;

        return $this;
    }

    public function getParalleleAchat(): ?string
    {
        return $this->parallele_achat;
    }

    public function setParalleleAchat(?string $parallele_achat): static
    {
        $this->parallele_achat = $parallele_achat;

        return $this;
    }

    public function getParalleleVente(): ?string
    {
        return $this->parallele_vente;
    }

    public function setParalleleVente(?string $parallele_vente): static
    {
        $this->parallele_vente = $parallele_vente;

        return $this;
    }

    public function getEcartIndicParallele(): ?string
    {
        return $this->ecart_indic_parallele;
    }

    public function setEcartIndicParallele(?string $ecart_indic_parallele): static
    {
        $this->ecart_indic_parallele = $ecart_indic_parallele;

        return $this;
    }
}
