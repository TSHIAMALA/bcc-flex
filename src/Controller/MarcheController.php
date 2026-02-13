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
        $latestEncours = $encoursRepository->getEncoursByPeriod($dateDebut, $dateFin);
        
        // Use filtered data
        $evolutionData = $marcheRepository->getEvolutionDataByPeriod($dateDebut, $dateFin);
        $reservesHistory = $reservesRepository->getReservesHistoryByPeriod($dateDebut, $dateFin);
        $marcheHistory = $marcheRepository->getEvolutionDataByPeriod($dateDebut, $dateFin);
        
        // Get latest data from filtered period only
        $latestMarche = !empty($evolutionData) ? end($evolutionData) : null;
        $latestReserves = !empty($reservesHistory) ? $reservesHistory[0] : null;

        // Calculate variation
        $varIndicatif = 0;
        if (count($marcheHistory) >= 2) {
            $count = count($marcheHistory);
            $latest = $marcheHistory[$count - 1];
            $previous = $marcheHistory[$count - 2];
            
            if ($previous->getCoursIndicatif() != 0) {
                $varIndicatif = (($latest->getCoursIndicatif() - $previous->getCoursIndicatif()) / $previous->getCoursIndicatif()) * 100;
            }
        }

        // Reverse history for table (DESC)
        $marcheTableData = array_reverse($marcheHistory);

        return $this->render('marche/index.html.twig', [
            'latestMarche' => $latestMarche,
            'volumes' => $volumes,
            'latestReserves' => $latestReserves,
            'encours' => $latestEncours,
            'evolutionData' => $evolutionData,
            'reservesHistory' => $reservesHistory,
            'marcheTableData' => $marcheTableData,
            'varIndicatif' => $varIndicatif,
            // Date filter parameters
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'periode' => $periode,
        ]);
    }
}

