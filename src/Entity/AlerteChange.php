<?php

namespace App\Entity;

use App\Repository\AlerteChangeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlerteChangeRepository::class)]
#[ORM\Table(name: 'alertes_change')]
class AlerteChange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ConjonctureJour::class)]
    #[ORM\JoinColumn(name: 'conjoncture_id', nullable: true)]
    private ?ConjonctureJour $conjoncture = null;

    #[ORM\ManyToOne(targetEntity: Indicateur::class, inversedBy: 'alertes')]
    #[ORM\JoinColumn(name: 'indicateur_id', nullable: true)]
    private ?Indicateur $indicateur = null;

    #[ORM\Column(type: 'decimal', precision: 18, scale: 4, nullable: true)]
    private ?string $valeur = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
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

    public function getValeur(): ?float
    {
        return $this->valeur !== null ? (float)$this->valeur : null;
    }

    public function setValeur(?string $valeur): static
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

    public function getStatusClass(): string
    {
        return match($this->statut) {
            'ALERTE' => 'danger',
            'VIGILANCE' => 'warning',
            default => 'success',
        };
    }

    public function getStatusIcon(): string
    {
        return match($this->statut) {
            'ALERTE' => 'exclamation-triangle',
            'VIGILANCE' => 'exclamation-circle',
            default => 'check-circle',
        };
    }
}
