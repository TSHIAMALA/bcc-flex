<?php

namespace App\Controller;

use App\Repository\MarcheChangesRepository;
use App\Repository\ReservesFinancieresRepository;
use App\Repository\VolumeUSDRepository;
use App\Repository\EncoursBccRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MarcheController extends AbstractController
{
    #[Route('/marche', name: 'app_marche')]
    public function index(
        MarcheChangesRepository $marcheRepository,
        ReservesFinancieresRepository $reservesRepository,
        VolumeUSDRepository $volumeRepository,
        EncoursBccRepository $encoursRepository
    ): Response {
        $latestMarche = $marcheRepository->findOneBy([], ['id' => 'DESC']);
        $volumes = $volumeRepository->getLatestVolumes();
        $latestReserves = $reservesRepository->findOneBy([], ['id' => 'DESC']); // Should join conjoncture for correct latest
        $latestEncours = $encoursRepository->getLatestEncours();
        
        $evolutionData = $marcheRepository->getEvolutionData(30);
        $reservesHistory = $reservesRepository->getReservesHistory(7);
        $marcheHistory = $marcheRepository->getEvolutionData(10); // Reusing getEvolutionData for history table

        // Calculate variation
        $varIndicatif = 0;
        if (count($marcheHistory) > 1) {
            $latest = $marcheHistory[count($marcheHistory) - 1]; // getEvolutionData sorts ASC, so last is latest? No, wait.
            // getEvolutionData sorts ASC. So last element is latest.
            // But for variation we need previous day.
            
            // Let's check getEvolutionData implementation: orderBy('c.date_situation', 'ASC')
            // So index N-1 is latest, N-2 is previous.
            $count = count($marcheHistory);
            if ($count >= 2) {
                $latest = $marcheHistory[$count - 1];
                $previous = $marcheHistory[$count - 2];
                
                if ($previous->getCoursIndicatif() != 0) {
                    $varIndicatif = (($latest->getCoursIndicatif() - $previous->getCoursIndicatif()) / $previous->getCoursIndicatif()) * 100;
                }
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
        ]);
    }
}
