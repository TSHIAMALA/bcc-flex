<?php

namespace App\Controller;

use App\Repository\KPIJournalierRepository;
use App\Repository\FinancesPubliquesRepository;
use App\Repository\MarcheChangesRepository;
use App\Repository\EncoursBccRepository;
use App\Repository\ReservesFinancieresRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AnalyseController extends AbstractController
{
    #[Route('/analyse', name: 'app_analyse')]
    public function index(
        Request $request,
        KPIJournalierRepository $kpiRepository,
        FinancesPubliquesRepository $financesRepository,
        MarcheChangesRepository $marcheRepository,
        EncoursBccRepository $encoursRepository,
        ReservesFinancieresRepository $reservesRepository
    ): Response {
        // Date filter parameters
        $periode = $request->query->get('periode', '30jours');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');

        // Calculate date range based on period
        if ($periode !== 'personnalise' || !$dateDebut || !$dateFin) {
            $dateFin = date('Y-m-d');
            switch ($periode) {
                case '7jours':
                    $dateDebut = date('Y-m-d', strtotime('-7 days'));
                    break;
                case '3mois':
                    $dateDebut = date('Y-m-d', strtotime('-3 months'));
                    break;
                default: // 30jours
                    $dateDebut = date('Y-m-d', strtotime('-30 days'));
            }
        }

        // Filtered data
        $kpiData = $kpiRepository->getKPIByPeriod($dateDebut, $dateFin);
        $latestKPI = !empty($kpiData) ? $kpiData[0] : null;
        
        $evolutionFinances = $financesRepository->getEvolutionDataByPeriod($dateDebut, $dateFin);
        $latestFinances = !empty($evolutionFinances) ? end($evolutionFinances) : null;
        
        $reservesData = $reservesRepository->getReservesHistoryByPeriod($dateDebut, $dateFin);
        $latestReserves = !empty($reservesData) ? $reservesData[0] : null;
        
        $latestEncours = $encoursRepository->getEncoursByPeriod($dateDebut, $dateFin);

        // Calculations
        
        // 1. Stabilité du Change
        $ecartChange = 0.0;
        $pressionChange = 0.0; // Pression par défaut
        $scoreStabilite = 0.0; // Score par défaut si pas de données
        
        if ($latestKPI) {
            $ecartChange = (float) $latestKPI->getEcartIndicParallele();
            $pressionChange = min(100, ($ecartChange / 150) * 100);
            $scoreStabilite = max(0, 100 - $pressionChange);
        }

        // 2. Niveau des Réserves
        $reservesInt = $latestKPI ? (float) $latestKPI->getReservesInternationalesUsd() : 0.0;
        $niveauReserves = min(100, ($reservesInt / 10000) * 100);

        // 3. Équilibre Budgétaire
        $recettes = $latestFinances ? (float) $latestFinances->getRecettesTotales() : 0.0;
        $depenses = $latestFinances ? (float) $latestFinances->getDepensesTotales() : 0.0;
        $ratioRD = ($depenses > 0) ? ($recettes / $depenses) * 100 : 0.0;
        $equilibreBudget = min(100, max(0, $ratioRD));

        // 4. Liquidité Marché
        $encoursTotal = ($latestEncours ? (float) $latestEncours->getEncoursOtBcc() : 0.0) + ($latestEncours ? (float) $latestEncours->getEncoursBBcc() : 0.0);
        $avoirsLibres = $latestReserves ? (float) $latestReserves->getAvoirsLibresCdf() : 0.0;
        $scoreLiquidite = 0.0;
        if ($encoursTotal > 0 || $avoirsLibres > 0) {
            $scoreLiquidite = (min(100, ($encoursTotal / 2000) * 50) + min(50, ($avoirsLibres / 500) * 50));
        }
        $liquiditeMarche = min(100, max(0, $scoreLiquidite));

        // 5. Croissance Économique
        $croissanceEconomique = 0.0; // Défaut 0 si pas de données
        $variationRecettes = 0.0;
        if (count($evolutionFinances) >= 2) {
            $recettesDebut = (float) $evolutionFinances[count($evolutionFinances) - 1]->getRecettesTotales();
            $recettesFin = (float) $evolutionFinances[0]->getRecettesTotales();
            // Éviter division par zéro
            if ($recettesDebut > 0) {
                $variationRecettes = (($recettesFin - $recettesDebut) / $recettesDebut) * 100;
                // Base 50 + variation
                $croissanceEconomique = max(0, min(100, 50 + ($variationRecettes * 5)));
            }
        } elseif ($latestFinances) {
             // Si une seule donnée, on met 50 par défaut (neutre) mais seulement si donnée existe
             $croissanceEconomique = 50.0;
        }

        // Score Global
        $scoreVigilance = (
            $scoreStabilite * 0.25 +
            $niveauReserves * 0.25 +
            $equilibreBudget * 0.20 +
            $liquiditeMarche * 0.15 +
            $croissanceEconomique * 0.15
        );

        $niveauVigilance = $scoreVigilance > 70 ? 'Favorable' : ($scoreVigilance > 40 ? 'Modéré' : 'Critique');
        // Si aucune donnée (score 0), on peut mettre un texte spécifique ou garder Critique
        if ($scoreVigilance == 0 && !$latestKPI && !$latestFinances) {
             $niveauVigilance = 'Non disponible';
        }
        
        $couleurVigilance = $scoreVigilance > 70 ? 'success' : ($scoreVigilance > 40 ? 'warning' : 'danger');
        if ($niveauVigilance === 'Non disponible') {
            $couleurVigilance = 'secondary';
        }

        $indicateurs = [
            ['nom' => 'Stabilité du Change', 'planifie' => 100, 'realise' => round($scoreStabilite, 1), 'couleur' => 'primary', 
             'source' => 'Écart indicatif/parallèle: ' . number_format($ecartChange, 0, ',', ' ') . ' CDF'],
            ['nom' => 'Niveau des Réserves', 'planifie' => 100, 'realise' => round($niveauReserves, 1), 'couleur' => 'info',
             'source' => 'Réserves internationales: ' . number_format($reservesInt, 0, ',', ' ') . ' Mio USD'],
            ['nom' => 'Équilibre Budgétaire', 'planifie' => 100, 'realise' => round($equilibreBudget, 1), 'couleur' => 'success',
             'source' => 'Ratio Recettes/Dépenses: ' . number_format($ratioRD, 1, ',', ' ') . '%'],
            ['nom' => 'Liquidité Marché', 'planifie' => 100, 'realise' => round($liquiditeMarche, 1), 'couleur' => 'warning',
             'source' => 'Encours BCC: ' . number_format($encoursTotal, 0, ',', ' ') . ' Mds'],
            ['nom' => 'Croissance Économique', 'planifie' => 100, 'realise' => round($croissanceEconomique, 1), 'couleur' => 'purple',
             'source' => 'Évolution recettes: ' . ($variationRecettes >= 0 ? '+' : '') . number_format($variationRecettes, 1, ',', ' ') . '%'],
        ];

        return $this->render('analyse/index.html.twig', [
            'kpiData' => $kpiData,
            'scoreVigilance' => $scoreVigilance,
            'niveauVigilance' => $niveauVigilance,
            'couleurVigilance' => $couleurVigilance,
            'indicateurs' => $indicateurs,
            'pressionChange' => $pressionChange,
            'niveauReserves' => $niveauReserves,
            'equilibreBudget' => $equilibreBudget,
            // Date filter parameters
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'periode' => $periode,
        ]);
    }
}
