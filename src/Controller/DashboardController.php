<?php

namespace App\Controller;

use App\Repository\ConjonctureJourRepository;
use App\Repository\FinancesPubliquesRepository;
use App\Repository\KPIJournalierRepository;
use App\Repository\MarcheChangesRepository;
use App\Repository\ReservesFinancieresRepository;
use App\Repository\VolumeUSDRepository;
use App\Repository\PaieEtatRepository;
use App\Service\AlerteService;
use App\Service\IndiceTensionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        KPIJournalierRepository $kpiRepository,
        MarcheChangesRepository $marcheRepository,
        FinancesPubliquesRepository $financesRepository,
        ReservesFinancieresRepository $reservesRepository,
        VolumeUSDRepository $volumeRepository,
        PaieEtatRepository $paieRepository,
        ConjonctureJourRepository $conjonctureRepository,
        AlerteService $alerteService,
        IndiceTensionService $itmService
    ): Response {
        $latestKPI = $kpiRepository->getLatestKPI();
        $previousKPI = $kpiRepository->getPreviousKPI();
        $kpiHistory = $kpiRepository->getKPIHistory(7);

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

        if ($latestKPI) {
            $latestMarche = $marcheRepository->findOneBy(['conjoncture' => $latestKPI->getConjonctureId()]);
            $latestReserves = $reservesRepository->findOneBy(['conjoncture' => $latestKPI->getConjonctureId()]);
            $latestFinances = $financesRepository->findOneBy(['conjoncture' => $latestKPI->getConjonctureId()]);
            $latestConjoncture = $conjonctureRepository->find($latestKPI->getConjonctureId());
        }

        // Calculate ITM (Indice de Tension du Marché)
        $itm = $itmService->calculateITM($latestConjoncture);
        $itmColor = $itmService->getClassificationColor($itm['classification']);
        $itmClass = $itmService->getClassificationClass($itm['classification']);

        // Get active alerts
        $activeAlerts = $alerteService->getFormattedAlerts();

        // Other data
        $evolutionMarche = $marcheRepository->getEvolutionData(7);
        $volumes = $volumeRepository->getLatestVolumes();
        $paie = $paieRepository->getLatestPaie();
        $reservesHistory = $reservesRepository->getReservesHistory(7);
        $financesHistory = $financesRepository->getEvolutionData(7);

        // Calculate total volume USD for KPI card
        $totalVolumeUSD = 0;
        foreach ($volumes as $vol) {
            $totalVolumeUSD += $vol->getVolumeTotalUsd() ?? 0;
        }

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
            // Active alerts
            'activeAlerts' => $activeAlerts,
            // Volume total for KPI
            'totalVolumeUSD' => $totalVolumeUSD,
        ]);
    }
}
