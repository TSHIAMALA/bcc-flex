<?php

namespace App\Service;

use App\Entity\AlerteChange;
use App\Entity\ConjonctureJour;
use App\Entity\Indicateur;
use App\Entity\RegleIntervention;
use App\Repository\AlerteChangeRepository;
use App\Repository\IndicateurRepository;
use App\Repository\RegleInterventionRepository;
use App\Repository\MarcheChangesRepository;
use App\Repository\ReservesFinancieresRepository;
use App\Repository\FinancesPubliquesRepository;
use Doctrine\ORM\EntityManagerInterface;

class AlerteService
{
    public const STATUS_NORMAL = 'NORMAL';
    public const STATUS_VIGILANCE = 'VIGILANCE';
    public const STATUS_ALERTE = 'ALERTE';

    public function __construct(
        private EntityManagerInterface $em,
        private IndicateurRepository $indicateurRepository,
        private RegleInterventionRepository $regleRepository,
        private AlerteChangeRepository $alerteRepository,
        private MarcheChangesRepository $marcheRepository,
        private ReservesFinancieresRepository $reservesRepository,
        private FinancesPubliquesRepository $financesRepository
    ) {}

    /**
     * Calculate and persist alerts for a given conjoncture
     */
    public function calculateAlerts(ConjonctureJour $conjoncture): array
    {
        $alerts = [];
        $indicators = $this->indicateurRepository->findAllWithRules();
        
        foreach ($indicators as $indicateur) {
            $value = $this->getIndicatorValue($indicateur, $conjoncture);
            
            if ($value === null) {
                continue;
            }
            
            foreach ($indicateur->getRegles() as $rule) {
                $status = $this->getAlertStatus($value, $rule);
                
                $alerte = new AlerteChange();
                $alerte->setConjoncture($conjoncture);
                $alerte->setIndicateur($indicateur);
                $alerte->setValeur((string)$value);
                $alerte->setStatut($status);
                
                $this->em->persist($alerte);
                $alerts[] = $alerte;
            }
        }
        
        $this->em->flush();
        
        return $alerts;
    }

    /**
     * Get alert status based on value and rule thresholds
     */
    public function getAlertStatus(float $value, RegleIntervention $rule): string
    {
        $seuilVigilance = $rule->getSeuilAlerte();
        $seuilIntervention = $rule->getSeuilIntervention();
        $sens = $rule->getSens();
        
        // Handle "hausse" direction (value increasing is bad)
        if ($sens === 'hausse') {
            if ($seuilIntervention !== null && $value >= $seuilIntervention) {
                return self::STATUS_ALERTE;
            }
            if ($seuilVigilance !== null && $value >= $seuilVigilance) {
                return self::STATUS_VIGILANCE;
            }
        }
        // Handle "baisse" direction (value decreasing is bad)
        elseif ($sens === 'baisse') {
            if ($seuilIntervention !== null && $value <= $seuilIntervention) {
                return self::STATUS_ALERTE;
            }
            if ($seuilVigilance !== null && $value <= $seuilVigilance) {
                return self::STATUS_VIGILANCE;
            }
        }
        
        return self::STATUS_NORMAL;
    }

    /**
     * Get indicator value from related entities
     */
    public function getIndicatorValue(Indicateur $indicateur, ConjonctureJour $conjoncture): ?float
    {
        $code = $indicateur->getCode();
        
        return match ($code) {
            'ECART_CHANGE' => $this->getEcartChange($conjoncture),
            'RESERVES_INT' => $this->getReservesInternationales($conjoncture),
            'SOLDE_BUDGET' => $this->getSoldeBudget($conjoncture),
            'COURS_INDICATIF' => $this->getCoursIndicatif($conjoncture),
            'VOLUME_USD' => $this->getVolumeUSD($conjoncture),
            default => null,
        };
    }

    private function getEcartChange(ConjonctureJour $conjoncture): ?float
    {
        $marche = $this->marcheRepository->findOneBy(['conjoncture' => $conjoncture]);
        return $marche ? (float)$marche->getEcartIndicParallele() : null;
    }

    private function getReservesInternationales(ConjonctureJour $conjoncture): ?float
    {
        $reserves = $this->reservesRepository->findOneBy(['conjoncture' => $conjoncture]);
        return $reserves ? (float)$reserves->getReservesInternationalesUsd() : null;
    }

    private function getSoldeBudget(ConjonctureJour $conjoncture): ?float
    {
        $finances = $this->financesRepository->findOneBy(['conjoncture' => $conjoncture]);
        return $finances ? (float)$finances->getSolde() : null;
    }

    private function getCoursIndicatif(ConjonctureJour $conjoncture): ?float
    {
        $marche = $this->marcheRepository->findOneBy(['conjoncture' => $conjoncture]);
        return $marche ? (float)$marche->getCoursIndicatif() : null;
    }

    private function getVolumeUSD(ConjonctureJour $conjoncture): ?float
    {
        // This would need to aggregate from transactions_usd or volumes table
        // For now return null - to be implemented based on actual data structure
        return null;
    }

    /**
     * Get active (non-normal) alerts
     */
    public function getActiveAlerts(int $limit = 20): array
    {
        return $this->alerteRepository->findActiveAlerts();
    }

    /**
     * Get formatted alerts for dashboard display
     */
    public function getFormattedAlerts(): array
    {
        $alerts = $this->alerteRepository->findActiveAlerts();
        $formatted = [];
        
        foreach ($alerts as $alerte) {
            $formatted[] = [
                'type' => $alerte->getStatut() === self::STATUS_ALERTE ? 'danger' : 'warning',
                'icon' => $this->getAlertIcon($alerte),
                'titre' => $alerte->getIndicateur()?->getLibelle() ?? 'Indicateur',
                'message' => $this->formatAlertMessage($alerte),
                'date' => $alerte->getConjoncture()?->getDateSituation() ?? $alerte->getCreatedAt(),
                'statut' => $alerte->getStatut(),
                'valeur' => $alerte->getValeur()
            ];
        }
        
        return $formatted;
    }

    /**
     * Get formatted alerts filtered by period
     */
    public function getFormattedAlertsByPeriod(string $dateDebut, string $dateFin): array
    {
        $alerts = $this->alerteRepository->findActiveAlertsByPeriod($dateDebut, $dateFin);
        $formatted = [];
        
        foreach ($alerts as $alerte) {
            $formatted[] = [
                'type' => $alerte->getStatut() === self::STATUS_ALERTE ? 'danger' : 'warning',
                'icon' => $this->getAlertIcon($alerte),
                'titre' => $alerte->getIndicateur()?->getLibelle() ?? 'Indicateur',
                'message' => $this->formatAlertMessage($alerte),
                'date' => $alerte->getConjoncture()?->getDateSituation() ?? $alerte->getCreatedAt(),
                'statut' => $alerte->getStatut(),
                'valeur' => $alerte->getValeur()
            ];
        }
        
        return $formatted;
    }

    private function getAlertIcon(AlerteChange $alerte): string
    {
        $code = $alerte->getIndicateur()?->getCode();
        
        return match ($code) {
            'ECART_CHANGE' => 'exchange-alt',
            'RESERVES_INT' => 'piggy-bank',
            'SOLDE_BUDGET' => 'chart-line',
            'COURS_INDICATIF' => 'university',
            'VOLUME_USD' => 'dollar-sign',
            default => 'exclamation-circle',
        };
    }

    private function formatAlertMessage(AlerteChange $alerte): string
    {
        $indicateur = $alerte->getIndicateur();
        $valeur = $alerte->getValeur();
        $unite = $indicateur?->getUnite() ?? '';
        
        return sprintf(
            'Valeur actuelle: %s %s - Statut: %s',
            number_format((float)$valeur, 2, ',', ' '),
            $unite,
            $alerte->getStatut()
        );
    }
}
