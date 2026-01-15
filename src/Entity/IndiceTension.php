<?php

namespace App\Entity;

use App\Repository\IndiceTensionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IndiceTensionRepository::class)]
#[ORM\Table(name: 'indice_tension')]
class IndiceTension
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_situation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $score = null; // 0 to 100

    #[ORM\Column(length: 20)]
    private ?string $niveau = null; // 'NORMAL', 'VIGILANCE', 'INTERVENTION'

    #[ORM\Column(type: Types::JSON)]
    private array $details = [];

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

    public function getScore(): ?string
    {
        return $this->score;
    }

    public function setScore(string $score): static
    {
        $this->score = $score;

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

    public function getDetails(): array
    {
        return $this->details;
    }

    public function setDetails(array $details): static
    {
        $this->details = $details;

        return $this;
    }
}
