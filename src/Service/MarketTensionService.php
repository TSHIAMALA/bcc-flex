<?php

namespace App\Service;

use App\Entity\AlerteChange;
use App\Entity\IndiceTension;
use App\Entity\KPIJournalier;
use App\Repository\AlerteChangeRepository;
use App\Repository\IndicateurRepository;
use App\Repository\IndiceTensionRepository;
use App\Repository\KPIJournalierRepository;
use App\Repository\RegleInterventionRepository;
use Doctrine\ORM\EntityManagerInterface;

class MarketTensionService
{
    private const INDICE_SEUIL_VIGILANCE = 30;
    private const INDICE_SEUIL_INTERVENTION = 60;

    public function __construct(
        private EntityManagerInterface $em,
        private RegleInterventionRepository $regleRepo,
        private AlerteChangeRepository $alerteRepo,
        private IndiceTensionRepository $indiceRepo,
        private KPIJournalierRepository $kpiRepo
    ) {
    }

    public function analyzeMarket(\DateTimeInterface $date): array
    {
        $kpi = $this->kpiRepo->findOneBy(['date_situation' => $date]);
        
        if (!$kpi) {
            return [
                'error' => 'No KPI data found for this date.'
            ];
        }

        // 1. Check Alerts
        $alerts = $this->checkAlerts($date, $kpi);

        // 2. Calculate Tension Index
        $indice = $this->calculateIndex($date, $kpi);

        return [
            'alerts' => $alerts,
            'indice' => $indice
        ];
    }

    private function checkAlerts(\DateTimeInterface $date, KPIJournalier $kpi): array
    {
        $regles = $this->regleRepo->findBy(['actif' => true]);
        $generatedAlerts = [];

        foreach ($regles as $regle) {
            $valeur = $this->getValueForIndicator($regle->getIndicateur()->getCode(), $kpi);
            
            if ($valeur === null) {
                continue;
            }

            $alertLevel = null;
            $threshold = null;

            // Simple logic: assume strictly greater/lower for now based on context, 
            // but normally should use $regle->getOperateur()
            // Here assuming generally high values are bad (e.g. high exchange rate gap)
            // For some indicators lower might be bad (e.g. reserves), logic needs to handle that.
            // Using operator from DB would be best.

            if ($this->compare($valeur, $regle->getSeuilIntervention(), $regle->getOperateur())) {
                $alertLevel = 'INTERVENTION';
                $threshold = $regle->getSeuilIntervention();
            } elseif ($this->compare($valeur, $regle->getSeuilAlerte(), $regle->getOperateur())) {
                $alertLevel = 'ALERTE';
                $threshold = $regle->getSeuilAlerte();
            }

            if ($alertLevel) {
                $alerte = new AlerteChange();
                $alerte->setDateAlerte($date);
                $alerte->setIndicateur($regle->getIndicateur());
                $alerte->setValeurConstatee((string)$valeur);
                $alerte->setSeuilDeclenche($threshold);
                $alerte->setNiveau($alertLevel);
                $alerte->setStatut('NOUVEAU');

                // Check if already exists to avoid dupes?
                // For now, persist.
                $this->em->persist($alerte);
                $generatedAlerts[] = $alerte;
            }
        }

        $this->em->flush();

        return $generatedAlerts;
    }

    private function calculateIndex(\DateTimeInterface $date, KPIJournalier $kpi): IndiceTension
    {
        $regles = $this->regleRepo->findBy(['actif' => true]);
        $totalScore = 0;
        $maxPossibleScore = 0;
        $details = [];

        foreach ($regles as $regle) {
            $poids = $regle->getPoids();
            if ($poids <= 0) continue;

            $maxPossibleScore += $poids;
            $valeur = $this->getValueForIndicator($regle->getIndicateur()->getCode(), $kpi);
            $localScore = 0;

            if ($valeur !== null) {
                if ($this->compare($valeur, $regle->getSeuilIntervention(), $regle->getOperateur())) {
                    $localScore = $poids; // Full weight
                } elseif ($this->compare($valeur, $regle->getSeuilAlerte(), $regle->getOperateur())) {
                    $localScore = $poids * 0.5; // Half weight
                }
            }
            
            $totalScore += $localScore;
            $details[$regle->getIndicateur()->getNom()] = [
                'valeur' => $valeur,
                'score' => $localScore,
                'poids' => $poids
            ];
        }

        // Normalize to 100 if weights sum up to something else, or assume weights sum to 100?
        // Let's assume weights sum to 100 usually. If not, we scale.
        $finalScore = $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0;

        $indice = new IndiceTension();
        $indice->setDateSituation($date);
        $indice->setScore((string)$finalScore);
        $indice->setDetails($details);

        if ($finalScore >= self::INDICE_SEUIL_INTERVENTION) {
            $indice->setNiveau('INTERVENTION');
        } elseif ($finalScore >= self::INDICE_SEUIL_VIGILANCE) {
            $indice->setNiveau('VIGILANCE');
        } else {
            $indice->setNiveau('NORMAL');
        }

        $this->em->persist($indice);
        $this->em->flush();

        return $indice;
    }

    private function getValueForIndicator(string $code, KPIJournalier $kpi): ?float
    {
        // Mapping codes to KPI fields
        return match (strtoupper($code)) {
            'ECART_CHANGE' => (float)$kpi->getEcartIndicParallele(),
            'RESERVES' => (float)$kpi->getReservesInternationalesUsd(),
            'AVOIRS_LIBRES' => (float)$kpi->getSolde(), // Proxy using Solde for now
            // Add other mappings as needed
            default => null,
        };
    }

    private function compare($valeur, $seuil, $operateur): bool
    {
        return match ($operateur) {
            '>' => $valeur > $seuil,
            '>=' => $valeur >= $seuil,
            '<' => $valeur < $seuil,
            '<=' => $valeur <= $seuil,
            default => false,
        };
    }
}
