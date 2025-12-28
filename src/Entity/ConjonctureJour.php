<?php

namespace App\Entity;

use App\Repository\ConjonctureJourRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConjonctureJourRepository::class)]
#[ORM\Table(name: 'conjoncture_jour')]
class ConjonctureJour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, unique: true)]
    private ?\DateTimeInterface $date_situation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_applicable = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateSituation(): ?\DateTimeInterface
    {
        return $this->date_situation;
    }

    public function setDateSituation(\DateTimeInterface $date_situation): static
    {
        $this->date_situation = $date_situation;

        return $this;
    }

    public function getDateApplicable(): ?\DateTimeInterface
    {
        return $this->date_applicable;
    }

    public function setDateApplicable(\DateTimeInterface $date_applicable): static
    {
        $this->date_applicable = $date_applicable;

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
