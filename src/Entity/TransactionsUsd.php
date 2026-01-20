<?php

namespace App\Entity;

use App\Repository\TransactionsUsdRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionsUsdRepository::class)]
#[ORM\Table(name: 'transactions_usd')]
class TransactionsUsd
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'conjoncture_id', referencedColumnName: 'id')]
    private ?ConjonctureJour $conjoncture = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'banque_id', referencedColumnName: 'id')]
    private ?Banques $banque = null;

    #[ORM\Column(length: 10)]
    private ?string $type_transaction = null; // ENUM('ACHAT', 'VENTE')

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $cours = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $volume_usd = null;

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

    public function getBanque(): ?Banques
    {
        return $this->banque;
    }

    public function setBanque(?Banques $banque): static
    {
        $this->banque = $banque;

        return $this;
    }

    public function getTypeTransaction(): ?string
    {
        return $this->type_transaction;
    }

    public function setTypeTransaction(string $type_transaction): static
    {
        $this->type_transaction = $type_transaction;

        return $this;
    }

    public function getCours(): ?string
    {
        return $this->cours;
    }

    public function setCours(?string $cours): static
    {
        $this->cours = $cours;

        return $this;
    }

    public function getVolumeUsd(): ?string
    {
        return $this->volume_usd;
    }

    public function setVolumeUsd(?string $volume_usd): static
    {
        $this->volume_usd = $volume_usd;

        return $this;
    }
}
