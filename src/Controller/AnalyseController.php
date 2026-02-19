<?php

namespace App\Controller;

use App\Repository\ConjonctureJourRepository;
use App\Repository\KPIJournalierRepository;
use App\Repository\FinancesPubliquesRepository;
use App\Repository\MarcheChangesRepository;
use App\Repository\EncoursBccRepository;
use App\Repository\ReservesFinancieresRepository;
use App\Repository\ParametreGlobalRepository;
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
        ReservesFinancieresRepository $reservesRepository,
        ConjonctureJourRepository $conjonctureRepository,
        ParametreGlobalRepository $paramRepo
    ): Response {
        // Date filter parameters
        $periode = $request->query->get('periode', '7jours');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');

        // Calculate date range based on period
        if ($periode !== 'personnalise' || !$dateDebut || !$dateFin) {
            // Use the latest conjoncture date as end date instead of today
            $latestConjonctureDateRef = $conjonctureRepository->findLatest();
            $endDate = $latestConjonctureDateRef ? clone $latestConjonctureDateRef->getDateSituation() : new \DateTime();
            $dateFin = $endDate->format('Y-m-d');
            $startDate = clone $endDate;
            switch ($periode) {
                case '7jours':
                    $dateDebut = $startDate->modify('-7 days')->format('Y-m-d');
                    break;
                case '3mois':
                    $dateDebut = $startDate->modify('-3 months')->format('Y-m-d');
                    break;
                default: // 30jours
                    $dateDebut = $startDate->modify('-30 days')->format('Y-m-d');
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
        $latestMarche = $latestKPI ? $marcheRepository->findMostRecentBeforeOrEqual($latestKPI->getDateSituation()) : null;

        // ==========================================================
        // FETCH PARAMETERS from PARAMETRE_GLOBAUX TABLE
        // ==========================================================
        $diviseurChange = $paramRepo->getValue('RADAR_CHANGE_DIVISEUR', 2);
        $reservesOptimal = $paramRepo->getValue('RADAR_RESERVES_OPTIMAL', 5000);
        $liquiditeOptimal = $paramRepo->getValue('RADAR_LIQUIDITE_OPTIMAL', 1000);
        $budgetBase = $paramRepo->getValue('RADAR_BUDGET_BASE', 60);
        $croissanceMult = $paramRepo->getValue('RADAR_CROISSANCE_MULT', 70);

        // Calculations (Using same logic as Dashboard)
        
        // 1. Stabilité du Change
        $ecartChange = 0.0;
        $scoreStabilite = 0.0;
        $pressionChange = 0.0;
        
        if ($latestMarche && $latestMarche->getEcartIndicParallele() !== null) {
            $ecartChange = (float) $latestMarche->getEcartIndicParallele();
            // Nouvelle logique : 100 - (Ecart / Diviseur), borné [0,100]
            $scoreStabilite = max(0, min(100, 100 - ($ecartChange / $diviseurChange)));
            // Pour compatibilité "pression" : inverse du score
            $pressionChange = 100 - $scoreStabilite;
        } elseif ($latestKPI) {
             // Fallback sur KPI si Marche non trouvé
            $ecartChange = (float) $latestKPI->getEcartIndicParallele();
            $scoreStabilite = max(0, min(100, 100 - ($ecartChange / $diviseurChange)));
            $pressionChange = 100 - $scoreStabilite;
        }

        // 2. Niveau des Réserves
        $reservesInt = $latestKPI ? (float) $latestKPI->getReservesInternationalesUsd() : 0.0;
        // Nouvelle logique : (Reserves / Optimal) * 100, borné [0,100]
        $niveauReserves = min(100, ($reservesInt / $reservesOptimal) * 100);

        // 3. Équilibre Budgétaire (Solde < 0 -> <60, Solde > 0 -> >60)
        // Note: AnalyseController utilisait Ratio R/D, Dashboard utilisait Solde.
        // On aligne AnalyseController sur Dashboard pour Solde, ou on garde logic Ratio ?
        // L'indicateur s'appelle "Équilibre Budgétaire" dans Analyse. Dashboard utilise Solde pour "Equilibre".
        // On va garder Ratio R/D ici car c'est plus précis pour "Analyse", mais utiliser les seuils cohérents.
        // Ratio R/D : > 100% = Excédent. 
        $recettes = $latestFinances ? (float) $latestFinances->getRecettesTotales() : 0.0;
        $depenses = $latestFinances ? (float) $latestFinances->getDepensesTotales() : 0.0;
        $ratioRD = ($depenses > 0) ? ($recettes / $depenses) * 100 : 0.0;
        // Si ratio = 100%, score = 60 (BASE). Si ratio = 0%, score = 0.
        // Si ratio = 200%, score = 100.
        // Formule alignée : min(100, max(0, Ratio * (100/param) ? non, simplifions)
        // Utilisons la logique Dashboard "Croissance/Perf" qui est basée sur ratio R/D
        $equilibreBudget = min(100, max(0, ($ratioRD/100) * $croissanceMult * 1.5)); // Scaling factor arbitraire pour "Analyse" ?
        // Ou mieux : on reprend la logique Dashboard "Equilibre Budget" basée sur le SOLDE si possible
        if ($latestFinances && $latestFinances->getSolde() !== null) {
             $soldeBudget = (float)$latestFinances->getSolde();
             if ($soldeBudget >= 0) {
                $equilibreBudget = min(100, $budgetBase + ($soldeBudget / 100));
            } else {
                $equilibreBudget = max(0, $budgetBase + ($soldeBudget / 50));
            }
        } else {
            // Fallback Ratio
            $equilibreBudget = min(100, max(0, ($ratioRD / 100) * 80)); 
        }


        // 4. Liquidité Marché
        // Dashboard uses AvoirsLibres. Analyse used Encours Total + AvoirsLibres.
        // Alignons sur Avoirs Libres (Dashboard logic) ou gardons complexité ?
        // Le Dashboard logic est plus "paramétré". Utilisons la logique Dashboard pour Liquidité.
        $avoirsLibres = $latestReserves ? (float) $latestReserves->getAvoirsLibresCdf() : 0.0;
        $liquiditeMarche = min(100, ($avoirsLibres / $liquiditeOptimal) * 100);
        $encoursTotal = ($latestEncours ? (float) $latestEncours->getEncoursOtBcc() : 0.0) + ($latestEncours ? (float) $latestEncours->getEncoursBBcc() : 0.0);

        // 5. Croissance Économique (Evolution Recettes)
        $croissanceEconomique = 0.0; // Défaut 0 si pas de données
        $variationRecettes = 0.0;
        if (count($evolutionFinances) >= 2) {
            $recettesDebut = (float) $evolutionFinances[count($evolutionFinances) - 1]->getRecettesTotales();
            $recettesFin = (float) $evolutionFinances[0]->getRecettesTotales();
            if ($recettesDebut > 0) {
                $variationRecettes = (($recettesFin - $recettesDebut) / $recettesDebut) * 100;
                $croissanceEconomique = max(0, min(100, 50 + ($variationRecettes * 5)));
            }
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
        if ($scoreVigilance == 0 && !$latestKPI && !$latestFinances) {
             $niveauVigilance = 'Non disponible';
        }
        
        $couleurVigilance = $scoreVigilance > 70 ? 'success' : ($scoreVigilance > 40 ? 'warning' : 'danger');
        if ($niveauVigilance === 'Non disponible') {
            $couleurVigilance = 'secondary';
        }

        $indicateurs = [
            ['nom' => 'Stabilité du Change', 'planifie' => 100, 'realise' => round($scoreStabilite, 1), 'couleur' => 'primary', 
             'source' => 'Écart indicatif/parallèle: ' . number_format($ecartChange, 2, ',', ' ') . ' CDF'],
            ['nom' => 'Niveau des Réserves', 'planifie' => 100, 'realise' => round($niveauReserves, 1), 'couleur' => 'info',
             'source' => 'Réserves internationales: ' . number_format($reservesInt, 0, ',', ' ') . ' Mio USD'],
            ['nom' => 'Équilibre Budgétaire', 'planifie' => 100, 'realise' => round($equilibreBudget, 1), 'couleur' => 'success',
             'source' => 'Solde Budgétaire: ' . (isset($soldeBudget) ? number_format($soldeBudget, 2, ',', ' ') : 'N/A') . ' Mds CDF'],
            ['nom' => 'Liquidité Marché', 'planifie' => 100, 'realise' => round($liquiditeMarche, 1), 'couleur' => 'warning',
             'source' => 'Avoirs Libres: ' . number_format($avoirsLibres, 0, ',', ' ') . ' Mds'],
            ['nom' => 'Croissance Économique', 'planifie' => 100, 'realise' => round($croissanceEconomique, 1), 'couleur' => 'purple',
             'source' => 'Variation recettes: ' . ($variationRecettes >= 0 ? '+' : '') . number_format($variationRecettes, 1, ',', ' ') . '%'],
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
