<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Repository\TauxDirecteurRepository;

#[ORM\Entity(repositoryClass: TauxDirecteurRepository::class)]
#[ORM\Table(name: 'taux_directeur')]
#[UniqueEntity(fields: ['dateApplication'], message: 'Il y a déjà un taux directeur défini pour cette date d\'application.')]
class TauxDirecteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateApplication = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $valeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commentaire = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateApplication(): ?\DateTimeInterface
    {
        return $this->dateApplication;
    }

    public function setDateApplication(\DateTimeInterface $dateApplication): static
    {
        $this->dateApplication = $dateApplication;

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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }
}
