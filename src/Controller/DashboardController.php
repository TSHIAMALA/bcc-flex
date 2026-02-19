<?php

namespace App\Controller;

use App\Repository\ConjonctureJourRepository;
use App\Repository\FinancesPubliquesRepository;
use App\Repository\KPIJournalierRepository;
use App\Repository\MarcheChangesRepository;
use App\Repository\ReservesFinancieresRepository;
use App\Repository\VolumeUSDRepository;
use App\Repository\PaieEtatRepository;
use App\Repository\ParametreGlobalRepository;
use App\Repository\ScoreItmDetailRepository;
use App\Service\AlerteService;
use App\Service\IndiceTensionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        Request $request,
        KPIJournalierRepository $kpiRepository,
        MarcheChangesRepository $marcheRepository,
        FinancesPubliquesRepository $financesRepository,
        ReservesFinancieresRepository $reservesRepository,
        \App\Repository\TransactionsUsdRepository $transacRepository,
        PaieEtatRepository $paieRepository,
        ConjonctureJourRepository $conjonctureRepository,
        ParametreGlobalRepository $paramGlobalRepository,
        ScoreItmDetailRepository $scoreItmDetailRepository,
        AlerteService $alerteService,
        IndiceTensionService $itmService
    ): Response {
        // Get date filter parameters
        $periode = $request->query->get('periode', '7jours'); // 7jours, 30jours, 3mois, personnalise
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');

        // Calculate date range based on period
        if ($periode !== 'personnalise' || !$dateDebut || !$dateFin) {
            // Use the latest conjoncture date as end date instead of today
            $latestConjonctureDateRef = $conjonctureRepository->findLatest();
            if ($latestConjonctureDateRef) {
                $endDate = clone $latestConjonctureDateRef->getDateSituation();
            } else {
                $endDate = new \DateTime();
            }
            $dateFin = $endDate->format('Y-m-d');
            $days = match($periode) {
                '30jours' => 30,
                '3mois' => 90,
                '90' => 90, // Backward compatibility
                '30' => 30, // Backward compatibility
                default => 7,
            };
            $startDate = clone $endDate;
            $dateDebut = $startDate->modify("-{$days} days")->format('Y-m-d');
        }

        // Fetch KPI data with date filter
        $latestKPI = $kpiRepository->getKPIByDate($dateDebut, $dateFin);
        
        // Get previous KPI based on the latest KPI date (Day-to-Day comparison)
        $previousKPI = null;
        if ($latestKPI) {
            $previousKPI = $kpiRepository->getPreviousKPIByDate($latestKPI->getDateSituation());
        }
        $kpiHistory = $kpiRepository->getKPIByPeriod($dateDebut, $dateFin);

        // Calculate variations
        $varCoursIndicatif = 0;
        $varReserves = 0;
        $varSolde = 0;

        if ($latestKPI && $previousKPI) {
            $varCoursIndicatif = $previousKPI->getCoursIndicatif() != 0 
                ? (($latestKPI->getCoursIndicatif() - $previousKPI->getCoursIndicatif()) / $previousKPI->getCoursIndicatif()) * 100 
                : 0;
            
            $varReserves = $previousKPI->getReservesInternationalesUsd() != 0
                ? (($latestKPI->getReservesInternationalesUsd() - $previousKPI->getReservesInternationalesUsd()) / $previousKPI->getReservesInternationalesUsd()) * 100
                : 0;
            
            $varSolde = $latestKPI->getSolde() - $previousKPI->getSolde();
        }

        // Fetch related data
        $latestMarche = null;
        $latestReserves = null;
        $latestFinances = null;
        $latestConjoncture = null;
        $scoreItmDetails = [];

        if ($latestKPI) {
            // Use date_situation to find related data since conjoncture_id is no longer available
            // Convert string date to DateTime for lookup
            $dateSituationStr = $latestKPI->getDateSituation();
            $dateSituation = $dateSituationStr ? new \DateTime($dateSituationStr) : null;
            $latestConjoncture = $dateSituation ? $conjonctureRepository->findOneBy(['date_situation' => $dateSituation]) : null;
            if ($latestConjoncture) {
                $latestMarche = $marcheRepository->findOneBy(['conjoncture' => $latestConjoncture]);
                $latestReserves = $reservesRepository->findOneBy(['conjoncture' => $latestConjoncture]);
                $latestFinances = $financesRepository->findOneBy(['conjoncture' => $latestConjoncture]);
            }
            
            // Fetch detailed scores for auditability
            if ($dateSituationStr) {
                try {
                    $scoreItmDetails = $scoreItmDetailRepository->getScoresForDate($dateSituationStr);
                } catch (\Exception $e) {
                    // Fail silently if view doesn't exist yet
                    $scoreItmDetails = [];
                }
            }
        }

        // Calculate ITM (Indice de Tension du Marché)
        $itm = $itmService->calculateITM($latestConjoncture);
        $itmColor = $itmService->getClassificationColor($itm['classification']);
        $itmClass = $itmService->getClassificationClass($itm['classification']);

        // Get active alerts
        $activeAlerts = $alerteService->getFormattedAlerts();

        // Other data with date filter
        $evolutionMarche = $marcheRepository->getEvolutionDataByPeriod($dateDebut, $dateFin);
        $volumes = $transacRepository->getLatestVolumesByBank();
        $paie = $paieRepository->getPaieByDate($dateDebut, $dateFin);
        $reservesHistory = $reservesRepository->getReservesHistoryByPeriod($dateDebut, $dateFin);
        $financesHistory = $financesRepository->getEvolutionDataByPeriod($dateDebut, $dateFin);

        // Calculate total volume USD for KPI card
        $totalVolumeUSD = 0;
        foreach ($volumes as $vol) {
            $totalVolumeUSD += $vol['volumeTotalUsd'] ?? 0;
        }

        // Calculate dynamic radar scores for Indicateurs de Vigilance (0-100 scale)
        $radarScores = $this->calculateRadarScores(
            $latestMarche,
            $latestReserves,
            $latestFinances,
            $latestKPI,
            $paramGlobalRepository
        );

        return $this->render('dashboard/index.html.twig', [
            'latestKPI' => $latestKPI,
            'previousKPI' => $previousKPI,
            'kpiData' => $kpiHistory,
            'varCoursIndicatif' => $varCoursIndicatif,
            'varReserves' => $varReserves,
            'varSolde' => $varSolde,
            'latestMarche' => $latestMarche,
            'latestReserves' => $latestReserves,
            'latestFinances' => $latestFinances,
            'evolutionMarche' => $evolutionMarche,
            'volumes' => $volumes,
            'paie' => $paie,
            'reservesHistory' => $reservesHistory,
            'financesHistory' => $financesHistory,
            // New ITM data
            'itm' => $itm,
            'itmColor' => $itmColor,
            'itmClass' => $itmClass,
            // Auditable Score Details
            'scoreItmDetails' => $scoreItmDetails,
            // Active alerts
            'activeAlerts' => $activeAlerts,
            // Volume total for KPI
            'totalVolumeUSD' => $totalVolumeUSD,
            // Date filter parameters
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'periode' => $periode,
            // Radar scores for Indicateurs de Vigilance
            'radarScores' => $radarScores,
        ]);
    }

    /**
     * Calculate radar scores for Indicateurs de Vigilance
     * Each score is between 0 and 100 (higher = better)
     * Returns null values when no data is available
     */
    private function calculateRadarScores(
        ?\App\Entity\MarcheChanges $marche,
        ?\App\Entity\ReservesFinancieres $reserves,
        ?\App\Entity\FinancesPubliques $finances,
        ?\App\Entity\KPIJournalier $kpi,
        ParametreGlobalRepository $params
    ): array {
        // Check if we have any data at all
        $hasData = $marche || $reserves || $finances;
        
        if (!$hasData) {
            // No data at all - return null for all indicators
            return [
                'stabiliteChange' => null,
                'niveauReserves' => null,
                'equilibreBudget' => null,
                'liquidite' => null,
                'croissance' => null,
                'hasData' => false,
            ];
        }

        // Fetch configured Parameters (with defaults if missing)
        $diviseurChange = $params->getValue('RADAR_CHANGE_DIVISEUR', 2);
        $reservesOptimal = $params->getValue('RADAR_RESERVES_OPTIMAL', 5000);
        $liquiditeOptimal = $params->getValue('RADAR_LIQUIDITE_OPTIMAL', 1000);
        $budgetBase = $params->getValue('RADAR_BUDGET_BASE', 60);
        $croissanceMult = $params->getValue('RADAR_CROISSANCE_MULT', 70);

        // 1. Stabilité Change: based on ecart indicatif/parallele
        // Lower ecart = more stability (100 - ecart/diviseur, capped at 0-100)
        // Only calculate if we have marche data with actual ecart value
        $stabiliteChange = null;
        if ($marche && $marche->getEcartIndicParallele() !== null) {
            $ecart = (float)$marche->getEcartIndicParallele();
            // Utilisation du paramètre configurable
            $stabiliteChange = max(0, min(100, 100 - ($ecart / $diviseurChange)));
        }

        // 2. Niveau Réserves: reserves in USD normalized
        // Using a reference of OPTIMAL value (e.g., 5000M) as 100%
        $niveauReserves = null;
        if ($reserves && $reserves->getReservesInternationalesUsd() !== null) {
            $reservesUsd = (float)$reserves->getReservesInternationalesUsd();
            $niveauReserves = min(100, ($reservesUsd / $reservesOptimal) * 100);
        }

        // 3. Équilibre Budget: based on solde budgetaire
        // Positive solde = good, negative solde = bad
        $equilibreBudget = null;
        if ($finances && $finances->getSolde() !== null) {
            $soldeBudget = (float)$finances->getSolde();
            if ($soldeBudget >= 0) {
                $equilibreBudget = min(100, $budgetBase + ($soldeBudget / 100));
            } else {
                $equilibreBudget = max(0, $budgetBase + ($soldeBudget / 50));
            }
        }

        // 4. Liquidité: based on avoirs libres CDF
        // Using a reference of OPTIMAL value (e.g., 1000B) as 100%
        $liquidite = null;
        if ($reserves && $reserves->getAvoirsLibresCdf() !== null) {
            $avoirsLibres = (float)$reserves->getAvoirsLibresCdf();
            $liquidite = min(100, ($avoirsLibres / $liquiditeOptimal) * 100);
        }

        // 5. Croissance / Performance: ratio recettes/depenses
        // ratio > 1 = excédent, ratio < 1 = déficit
        $croissance = null;
        if ($finances && $finances->getRecettesTotales() !== null && $finances->getDepensesTotales() !== null) {
            $recettes = (float)$finances->getRecettesTotales();
            $depenses = (float)$finances->getDepensesTotales();
            if ($depenses > 0 && $recettes > 0) {
                $ratio = $recettes / $depenses;
                $croissance = min(100, max(0, $ratio * $croissanceMult));
            }
        }

        return [
            'stabiliteChange' => $stabiliteChange !== null ? round($stabiliteChange, 1) : null,
            'niveauReserves' => $niveauReserves !== null ? round($niveauReserves, 1) : null,
            'equilibreBudget' => $equilibreBudget !== null ? round($equilibreBudget, 1) : null,
            'liquidite' => $liquidite !== null ? round($liquidite, 1) : null,
            'croissance' => $croissance !== null ? round($croissance, 1) : null,
            'hasData' => true,
        ];
    }
}
