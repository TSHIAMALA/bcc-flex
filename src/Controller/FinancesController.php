<?php

namespace App\Controller;

use App\Repository\FinancesPubliquesRepository;
use App\Repository\TresorerieEtatRepository;
use App\Repository\TitresPublicsRepository;
use App\Repository\PaieEtatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FinancesController extends AbstractController
{
    #[Route('/finances', name: 'app_finances')]
    public function index(
        FinancesPubliquesRepository $financesRepository,
        TresorerieEtatRepository $tresorerieRepository,
        TitresPublicsRepository $titresRepository,
        PaieEtatRepository $paieRepository
    ): Response {
        $latestFin = $financesRepository->findOneBy([], ['id' => 'DESC']);
        $tresorerie = $tresorerieRepository->getLatestTresorerie();
        $titres = $titresRepository->getLatestTitres();
        $paie = $paieRepository->getLatestPaie();
        
        $evolutionData = $financesRepository->getEvolutionData(30);
        $finData = $financesRepository->getEvolutionData(10); // History table

        // Calculate variations
        $varRecettes = 0;
        $varDepenses = 0;
        
        // Logic for variations if needed, similar to MarcheController

        return $this->render('finances/index.html.twig', [
            'latestFin' => $latestFin,
            'tresorerie' => $tresorerie,
            'titres' => $titres,
            'paie' => $paie,
            'evolutionData' => $evolutionData,
            'finData' => array_reverse($finData),
        ]);
    }
}
