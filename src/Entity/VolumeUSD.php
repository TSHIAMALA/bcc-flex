<?php

namespace App\Entity;

use App\Repository\VolumeUSDRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VolumeUSDRepository::class, readOnly: true)]
#[ORM\Table(name: 'v_volumes_usd_par_banque')]
class VolumeUSD
{
    #[ORM\Id]
    #[ORM\Column]
    private ?string $banque = null; // Assuming banque is unique per date, but it's not. Composite key needed or just use as DTO.
    // Actually, Doctrine needs a PK. The view doesn't have a clear PK.
    // I'll assume (date_situation, banque, type_transaction) is unique.
    
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING)]
    private ?string $date_situation = null;

    #[ORM\Id]
    #[ORM\Column]
    private ?string $type_transaction = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 40, scale: 2, nullable: true)]
    private ?string $volume_total_usd = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 16, scale: 8, nullable: true)]
    private ?string $cours_moyen = null;

    public function getBanque(): ?string
    {
        return $this->banque;
    }

    public function getDateSituation(): ?string
    {
        return $this->date_situation;
    }

    public function getTypeTransaction(): ?string
    {
        return $this->type_transaction;
    }

    public function getVolumeTotalUsd(): ?string
    {
        return $this->volume_total_usd;
    }

    public function getCoursMoyen(): ?string
    {
        return $this->cours_moyen;
    }
}
