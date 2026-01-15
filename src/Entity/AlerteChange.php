<?php

namespace App\Entity;

use App\Repository\AlerteChangeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlerteChangeRepository::class)]
#[ORM\Table(name: 'alerte_change')]
class AlerteChange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_alerte = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Indicateur $indicateur = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private ?string $valeur_constatee = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private ?string $seuil_declenche = null;

    #[ORM\Column(length: 20)]
    private ?string $niveau = null; // 'ALERTE', 'INTERVENTION'

    #[ORM\Column(length: 20)]
    private ?string $statut = 'NOUVEAU'; // 'NOUVEAU', 'TRAITE', 'IGNORE'

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateAlerte(): ?\DateTimeInterface
    {
        return $this->date_alerte;
    }

    public function setDateAlerte(\DateTimeInterface $date_alerte): static
    {
        $this->date_alerte = $date_alerte;

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

    public function getValeurConstatee(): ?string
    {
        return $this->valeur_constatee;
    }

    public function setValeurConstatee(string $valeur_constatee): static
    {
        $this->valeur_constatee = $valeur_constatee;

        return $this;
    }

    public function getSeuilDeclenche(): ?string
    {
        return $this->seuil_declenche;
    }

    public function setSeuilDeclenche(string $seuil_declenche): static
    {
        $this->seuil_declenche = $seuil_declenche;

        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): static
    {
        $this->niveau = $niveau;

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
}
