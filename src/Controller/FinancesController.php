<?php

namespace App\Controller;

use App\Repository\FinancesPubliquesRepository;
use App\Repository\TresorerieEtatRepository;
use App\Repository\TitresPublicsRepository;
use App\Repository\PaieEtatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FinancesController extends AbstractController
{
    #[Route('/finances', name: 'app_finances')]
    public function index(
        Request $request,
        FinancesPubliquesRepository $financesRepository,
        TresorerieEtatRepository $tresorerieRepository,
        TitresPublicsRepository $titresRepository,
        PaieEtatRepository $paieRepository
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
                case '6mois':
                    $dateDebut = date('Y-m-d', strtotime('-6 months'));
                    break;
                case '1an':
                    $dateDebut = date('Y-m-d', strtotime('-1 year'));
                    break;
                default: // 30jours
                    $dateDebut = date('Y-m-d', strtotime('-30 days'));
            }
        }

        // All data filtered by period
        $tresorerie = $tresorerieRepository->getTresorerieByPeriod($dateDebut, $dateFin);
        $titres = $titresRepository->getTitresByPeriod($dateDebut, $dateFin);
        $paie = $paieRepository->getPaieByDate($dateDebut, $dateFin);
        
        // Use filtered data
        $evolutionData = $financesRepository->getEvolutionDataByPeriod($dateDebut, $dateFin);
        $finData = $financesRepository->getEvolutionDataByPeriod($dateDebut, $dateFin);
        
        // Get latest data from filtered period only
        $latestFin = !empty($evolutionData) ? end($evolutionData) : null;
        
        // Calculate aggregated totals for the period (sum of all entries in period)
        $totalRecettes = 0;
        $totalDepenses = 0;
        foreach ($evolutionData as $entry) {
            $totalRecettes += (float) $entry->getRecettesTotales();
            $totalDepenses += (float) $entry->getDepensesTotales();
        }
        $soldePeriode = $totalRecettes - $totalDepenses;
        
        // Create period stats for display if there's more than one entry
        $periodStats = [
            'recettesTotales' => count($evolutionData) > 1 ? $totalRecettes : ($latestFin ? $latestFin->getRecettesTotales() : 0),
            'depensesTotales' => count($evolutionData) > 1 ? $totalDepenses : ($latestFin ? $latestFin->getDepensesTotales() : 0),
            'solde' => count($evolutionData) > 1 ? $soldePeriode : ($latestFin ? $latestFin->getSolde() : 0),
            'nbEntries' => count($evolutionData),
        ];

        return $this->render('finances/index.html.twig', [
            'latestFin' => $latestFin,
            'periodStats' => $periodStats,
            'tresorerie' => $tresorerie,
            'titres' => $titres,
            'paie' => $paie,
            'evolutionData' => $evolutionData,
            'finData' => array_reverse($finData),
            // Date filter parameters
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'periode' => $periode,
        ]);
    }
}

