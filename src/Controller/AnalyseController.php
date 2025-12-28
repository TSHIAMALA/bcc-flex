<?php

namespace App\Controller;

use App\Repository\KPIJournalierRepository;
use App\Repository\FinancesPubliquesRepository;
use App\Repository\MarcheChangesRepository;
use App\Repository\EncoursBccRepository;
use App\Repository\ReservesFinancieresRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AnalyseController extends AbstractController
{
    #[Route('/analyse', name: 'app_analyse')]
    public function index(
        KPIJournalierRepository $kpiRepository,
        FinancesPubliquesRepository $financesRepository,
        MarcheChangesRepository $marcheRepository,
        EncoursBccRepository $encoursRepository,
        ReservesFinancieresRepository $reservesRepository
    ): Response {
        $kpiData = $kpiRepository->getKPIHistory(10);
        $latestKPI = $kpiRepository->getLatestKPI();
        $latestFinances = $financesRepository->findOneBy([], ['id' => 'DESC']);
        $latestReserves = $reservesRepository->findOneBy([], ['id' => 'DESC']);
        $latestEncours = $encoursRepository->getLatestEncours();
        $evolutionFinances = $financesRepository->getEvolutionData(7);

        // Calculations
        $ecartChange = $latestKPI ? $latestKPI->getEcartIndicParallele() : 0;
        $pressionChange = min(100, ($ecartChange / 150) * 100);

        $reservesInt = $latestKPI ? $latestKPI->getReservesInternationalesUsd() : 0;
        $niveauReserves = min(100, ($reservesInt / 10000) * 100);

        $recettes = $latestFinances ? $latestFinances->getRecettesTotales() : 1;
        $depenses = $latestFinances ? $latestFinances->getDepensesTotales() : 1;
        $ratioRD = $depenses > 0 ? ($recettes / $depenses) * 100 : 100;
        $equilibreBudget = min(100, max(0, $ratioRD));

        $encoursTotal = ($latestEncours ? $latestEncours->getEncoursOtBcc() : 0) + ($latestEncours ? $latestEncours->getEncoursBBcc() : 0);
        $avoirsLibres = $latestReserves ? $latestReserves->getAvoirsLibresCdf() : 0;
        $scoreLiquidite = (min(100, ($encoursTotal / 2000) * 50) + min(50, ($avoirsLibres / 500) * 50));
        $liquiditeMarche = min(100, max(0, $scoreLiquidite));

        $croissanceEconomique = 50;
        $variationRecettes = 0;
        if (count($evolutionFinances) >= 2) {
            $recettesDebut = $evolutionFinances[count($evolutionFinances) - 1]->getRecettesTotales();
            $recettesFin = $evolutionFinances[0]->getRecettesTotales();
            $variationRecettes = $recettesDebut > 0 ? (($recettesFin - $recettesDebut) / $recettesDebut) * 100 : 0;
            $croissanceEconomique = max(0, min(100, 50 + ($variationRecettes * 5)));
        }

        $scoreVigilance = (
            (100 - $pressionChange) * 0.25 +
            $niveauReserves * 0.25 +
            $equilibreBudget * 0.20 +
            $liquiditeMarche * 0.15 +
            $croissanceEconomique * 0.15
        );

        $niveauVigilance = $scoreVigilance > 70 ? 'Favorable' : ($scoreVigilance > 40 ? 'Modéré' : 'Critique');
        $couleurVigilance = $scoreVigilance > 70 ? 'success' : ($scoreVigilance > 40 ? 'warning' : 'danger');

        $indicateurs = [
            ['nom' => 'Stabilité du Change', 'planifie' => 100, 'realise' => round(100 - $pressionChange, 1), 'couleur' => 'primary', 
             'source' => 'Écart indicatif/parallèle: ' . number_format($ecartChange, 0, ',', ' ') . ' CDF'],
            ['nom' => 'Niveau des Réserves', 'planifie' => 100, 'realise' => round($niveauReserves, 1), 'couleur' => 'info',
             'source' => 'Réserves internationales: ' . number_format($reservesInt, 0, ',', ' ') . ' Mio USD'],
            ['nom' => 'Équilibre Budgétaire', 'planifie' => 100, 'realise' => round($equilibreBudget, 1), 'couleur' => 'success',
             'source' => 'Ratio Recettes/Dépenses: ' . number_format($ratioRD, 1, ',', ' ') . '%'],
            ['nom' => 'Liquidité Marché', 'planifie' => 100, 'realise' => round($liquiditeMarche, 1), 'couleur' => 'warning',
             'source' => 'Encours BCC: ' . number_format($encoursTotal, 0, ',', ' ') . ' Mds'],
            ['nom' => 'Croissance Économique', 'planifie' => 100, 'realise' => round($croissanceEconomique, 1), 'couleur' => 'purple',
             'source' => 'Évolution recettes 7j: ' . ($variationRecettes >= 0 ? '+' : '') . number_format($variationRecettes, 1, ',', ' ') . '%'],
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
        ]);
    }
}
