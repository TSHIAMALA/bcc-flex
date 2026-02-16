<?php

namespace App\Controller;

use App\Repository\ConjonctureJourRepository;
use App\Repository\MarcheChangesRepository;
use App\Repository\ReservesFinancieresRepository;
use App\Repository\EncoursBccRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MarcheController extends AbstractController
{
    #[Route('/marche', name: 'app_marche')]
    public function index(
        Request $request,
        MarcheChangesRepository $marcheRepository,
        ReservesFinancieresRepository $reservesRepository,
        \App\Repository\TransactionsUsdRepository $transacRepository,
        EncoursBccRepository $encoursRepository,
        ConjonctureJourRepository $conjonctureRepository
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
                case '6mois':
                    $dateDebut = $startDate->modify('-6 months')->format('Y-m-d');
                    break;
                case '1an':
                    $dateDebut = $startDate->modify('-1 year')->format('Y-m-d');
                    break;
                default: // 30jours
                    $dateDebut = $startDate->modify('-30 days')->format('Y-m-d');
            }
        }

        // Filtered data by period
        $volumes = $transacRepository->getVolumesByBankForPeriod($dateDebut, $dateFin);
        // Get latest encours data (closest to end date, finding previous value if needed)
        $latestEncours = $encoursRepository->findMostRecentBeforeOrEqual($dateFin);
        
        // Use filtered data
        $evolutionData = $marcheRepository->getEvolutionDataByPeriod($dateDebut, $dateFin);
        $reservesHistory = $reservesRepository->getReservesHistoryByPeriod($dateDebut, $dateFin);
        
        // Get latest data from filtered period only (closest to end date)
        $latestMarche = $marcheRepository->findMostRecentBeforeOrEqual($dateFin);
        $latestReserves = $reservesRepository->findMostRecentBeforeOrEqual($dateFin);

        // Calculate variation: Compare LATEST date with PREVIOUS date (Day-to-Day)
        $varIndicatif = 0;
        if ($latestMarche) {
            // Get the date of the latest data point
            $latestDate = $latestMarche->getConjoncture()->getDateSituation();
            // Find the data point immediately preceding this date
            $previousMarche = $marcheRepository->findMostRecentBefore($latestDate);
            
            if ($previousMarche && $previousMarche->getCoursIndicatif() != 0) {
                $varIndicatif = (($latestMarche->getCoursIndicatif() - $previousMarche->getCoursIndicatif()) / $previousMarche->getCoursIndicatif()) * 100;
            }
        }

        // Calculate reserves variation (Day-to-Day)
        $varReserves = 0;
        if ($latestReserves) {
            $latestResDate = $latestReserves->getConjoncture()->getDateSituation();
            $previousReserves = $reservesRepository->findMostRecentBefore($latestResDate);

            if ($previousReserves && $previousReserves->getReservesInternationalesUsd() != 0) {
                $varReserves = (($latestReserves->getReservesInternationalesUsd() - $previousReserves->getReservesInternationalesUsd()) / $previousReserves->getReservesInternationalesUsd()) * 100;
            }
        }

        // Reverse history for table (DESC)
        $marcheTableData = array_reverse($evolutionData);

        return $this->render('marche/index.html.twig', [
            'latestMarche' => $latestMarche,
            'volumes' => $volumes,
            'latestReserves' => $latestReserves,
            'encours' => $latestEncours,
            'evolutionData' => $evolutionData,
            'reservesHistory' => $reservesHistory,
            'marcheTableData' => $marcheTableData,
            'varIndicatif' => $varIndicatif,
            'varReserves' => $varReserves,
            // Date filter parameters
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'periode' => $periode,
        ]);
    }
}

