<?php

namespace App\Entity;

use App\Repository\AlerteChangeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlerteChangeRepository::class)]
class AlerteChange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ConjonctureJour::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConjonctureJour $conjoncture = null;

    #[ORM\ManyToOne(targetEntity: Indicateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Indicateur $indicateur = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private ?string $valeur = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = 'NORMAL';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

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

    public function getIndicateur(): ?Indicateur
    {
        return $this->indicateur;
    }

    public function setIndicateur(?Indicateur $indicateur): static
    {
        $this->indicateur = $indicateur;
        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): static
    {
        $this->valeur = $valeur;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
