<?php

namespace App\Entity;

use App\Repository\ParametreGlobalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParametreGlobalRepository::class)]
#[ORM\Table(name: 'parametre_globaux')]
class ParametreGlobal
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private ?string $code = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private ?string $valeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
