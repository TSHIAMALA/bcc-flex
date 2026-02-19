<?php

namespace App\Entity;

use App\Repository\ScoreItmDetailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScoreItmDetailRepository::class, readOnly: true)]
#[ORM\Table(name: 'v_score_itm_detail')]
class ScoreItmDetail
{
    #[ORM\Id]
    #[ORM\Column(length: 10)] // date_situation + indicateur_code = PK virtuelle
    private ?string $pk = null; // Juste pour Doctrine, on n'utilise pas vraiment ça

    #[ORM\Column(length: 50)]
    private ?string $indicateur_code = null;

    #[ORM\Column(length: 255)]
    private ?string $indicateur = null;
    
    #[ORM\Column(length: 10)]
    private ?string $date_situation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2)]
    private ?string $valeur_brute = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2)]
    private ?string $score_calcule = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $poids = null;
    
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $seuil_alerte = null;
    
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $seuil_intervention = null;

    public function getIndicateurCode(): ?string { return $this->indicateur_code; }
    public function getIndicateur(): ?string { return $this->indicateur; }
    public function getDateSituation(): ?string { return $this->date_situation; }
    public function getScoreCalcule(): ?float { return (float)$this->score_calcule; }
    public function getValeurBrute(): ?float { return (float)$this->valeur_brute; }
    public function getPoids(): ?int { return $this->poids; }
    public function getSeuilAlerte(): ?float { return (float)$this->seuil_alerte; }
    public function getSeuilIntervention(): ?float { return (float)$this->seuil_intervention; }
}
