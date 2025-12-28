<?php

namespace App\Controller;

use App\Repository\FinancesPubliquesRepository;
use App\Repository\KPIJournalierRepository;
use App\Repository\MarcheChangesRepository;
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
        \App\Repository\ReservesFinancieresRepository $reservesRepository,
        \App\Repository\VolumeUSDRepository $volumeRepository,
        \App\Repository\PaieEtatRepository $paieRepository
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

        // Fetch other data
        $latestMarche = $marcheRepository->findOneBy([], ['id' => 'DESC']); // Assuming ID order is chronological, or join conjoncture
        // Better to use a custom method or join, but for now let's try to get the one matching latestKPI
        if ($latestKPI) {
            $latestMarche = $marcheRepository->findOneBy(['conjoncture' => $latestKPI->getConjonctureId()]);
            $latestReserves = $reservesRepository->findOneBy(['conjoncture' => $latestKPI->getConjonctureId()]);
            $latestFinances = $financesRepository->findOneBy(['conjoncture' => $latestKPI->getConjonctureId()]);
        } else {
            $latestMarche = null;
            $latestReserves = null;
            $latestFinances = null;
        }

        $evolutionMarche = $marcheRepository->getEvolutionData(7);
        $volumes = $volumeRepository->getLatestVolumes();
        $paie = $paieRepository->getLatestPaie();
        $reservesHistory = $reservesRepository->getReservesHistory(7);
        $financesHistory = $financesRepository->getEvolutionData(7);

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
        ]);
    }
}
