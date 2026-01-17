<?php

namespace App\Service;

use App\Entity\ConjonctureJour;
use App\Entity\RegleIntervention;
use App\Repository\IndicateurRepository;
use App\Repository\RegleInterventionRepository;

class IndiceTensionService
{
    public const CLASSIFICATION_NORMAL = 'NORMAL';
    public const CLASSIFICATION_VIGILANCE = 'VIGILANCE';
    public const CLASSIFICATION_INTERVENTION = 'INTERVENTION';

    public const SEUIL_VIGILANCE = 30;
    public const SEUIL_INTERVENTION = 60;

    public function __construct(
        private AlerteService $alerteService,
        private IndicateurRepository $indicateurRepository,
        private RegleInterventionRepository $regleRepository
    ) {}

    /**
     * Calculate the ITM (Indice de Tension du Marché) score
     * Returns a score between 0 and 100
     */
    public function calculateITM(?ConjonctureJour $conjoncture): array
    {
        if (!$conjoncture) {
            return [
                'score' => 0,
                'classification' => self::CLASSIFICATION_NORMAL,
                'label' => 'Données indisponibles',
                'details' => []
            ];
        }

        $indicators = $this->indicateurRepository->findAllWithRules();
        $totalWeight = 0;
        $weightedScore = 0;
        $details = [];

        foreach ($indicators as $indicateur) {
            $value = $this->alerteService->getIndicatorValue($indicateur, $conjoncture);
            
            if ($value === null) {
                continue;
            }

            foreach ($indicateur->getRegles() as $rule) {
                $weight = $rule->getPoids() ?? 1;
                $indicatorScore = $this->calculateIndicatorScore($value, $rule);
                
                $totalWeight += $weight;
                $weightedScore += $indicatorScore * $weight;
                
                $details[] = [
                    'indicateur' => $indicateur->getLibelle(),
                    'code' => $indicateur->getCode(),
                    'valeur' => $value,
                    'score' => $indicatorScore,
                    'poids' => $weight,
                    'statut' => $this->alerteService->getAlertStatus($value, $rule)
                ];
            }
        }

        $score = $totalWeight > 0 ? ($weightedScore / $totalWeight) : 0;
        $score = min(100, max(0, $score)); // Clamp between 0 and 100
        
        $classification = $this->getITMClassification($score);
        
        return [
            'score' => round($score, 1),
            'classification' => $classification,
            'label' => $this->getClassificationLabel($classification),
            'details' => $details
        ];
    }

    /**
     * Calculate individual indicator score (0-100)
     */
    private function calculateIndicatorScore(float $value, RegleIntervention $rule): float
    {
        $seuilVigilance = $rule->getSeuilAlerte();
        $seuilIntervention = $rule->getSeuilIntervention();
        $sens = $rule->getSens();

        // If no thresholds defined, return 0 (no tension)
        if ($seuilVigilance === null || $seuilIntervention === null) {
            return 0;
        }

        // For "hausse" - higher values mean more tension
        if ($sens === 'hausse') {
            if ($value <= $seuilVigilance) {
                // Below vigilance threshold: 0-30 score
                return ($value / $seuilVigilance) * self::SEUIL_VIGILANCE;
            } elseif ($value <= $seuilIntervention) {
                // Between vigilance and intervention: 30-60 score
                $range = $seuilIntervention - $seuilVigilance;
                $position = ($value - $seuilVigilance) / $range;
                return self::SEUIL_VIGILANCE + ($position * (self::SEUIL_INTERVENTION - self::SEUIL_VIGILANCE));
            } else {
                // Above intervention: 60-100 score
                $excess = $value - $seuilIntervention;
                $maxExcess = $seuilIntervention * 0.5; // Assume 50% excess = 100 score
                $position = min(1, $excess / $maxExcess);
                return self::SEUIL_INTERVENTION + ($position * (100 - self::SEUIL_INTERVENTION));
            }
        }
        // For "baisse" - lower values mean more tension
        elseif ($sens === 'baisse') {
            if ($value >= $seuilVigilance) {
                // Above vigilance threshold: 0-30 score
                return (1 - min(1, $value / ($seuilVigilance * 2))) * self::SEUIL_VIGILANCE;
            } elseif ($value >= $seuilIntervention) {
                // Between intervention and vigilance: 30-60 score
                $range = $seuilVigilance - $seuilIntervention;
                $position = ($seuilVigilance - $value) / $range;
                return self::SEUIL_VIGILANCE + ($position * (self::SEUIL_INTERVENTION - self::SEUIL_VIGILANCE));
            } else {
                // Below intervention: 60-100 score
                $deficit = $seuilIntervention - $value;
                $maxDeficit = $seuilIntervention * 0.5;
                $position = min(1, $deficit / $maxDeficit);
                return self::SEUIL_INTERVENTION + ($position * (100 - self::SEUIL_INTERVENTION));
            }
        }

        return 0;
    }

    /**
     * Get classification based on ITM score
     */
    public function getITMClassification(float $score): string
    {
        if ($score >= self::SEUIL_INTERVENTION) {
            return self::CLASSIFICATION_INTERVENTION;
        } elseif ($score >= self::SEUIL_VIGILANCE) {
            return self::CLASSIFICATION_VIGILANCE;
        }
        
        return self::CLASSIFICATION_NORMAL;
    }

    /**
     * Get human-readable label for classification
     */
    public function getClassificationLabel(string $classification): string
    {
        return match ($classification) {
            self::CLASSIFICATION_INTERVENTION => 'Intervention recommandée',
            self::CLASSIFICATION_VIGILANCE => 'Vigilance requise',
            self::CLASSIFICATION_NORMAL => 'Situation normale',
            default => 'Non déterminé',
        };
    }

    /**
     * Get CSS class for classification
     */
    public function getClassificationClass(string $classification): string
    {
        return match ($classification) {
            self::CLASSIFICATION_INTERVENTION => 'danger',
            self::CLASSIFICATION_VIGILANCE => 'warning',
            self::CLASSIFICATION_NORMAL => 'success',
            default => 'info',
        };
    }

    /**
     * Get color hex for gauge display
     */
    public function getClassificationColor(string $classification): string
    {
        return match ($classification) {
            self::CLASSIFICATION_INTERVENTION => '#ef4444',
            self::CLASSIFICATION_VIGILANCE => '#f59e0b',
            self::CLASSIFICATION_NORMAL => '#10b981',
            default => '#64748b',
        };
    }
}
