<?php

namespace App\Controller;

use App\Repository\ConjonctureJourRepository;
use App\Repository\TransactionsUsdRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BanquesAnalyseController extends AbstractController
{
    #[Route('/banques-analyse', name: 'app_banques_analyse')]
    public function index(
        Request $request,
        TransactionsUsdRepository $transactionsRepository,
        ConjonctureJourRepository $conjonctureRepository
    ): Response {
        // Date filter parameters
        $periode = $request->query->get('periode', '7jours');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');

        // Calculate date range based on period
        if ($periode !== 'personnalise' || !$dateDebut || !$dateFin) {
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

        // Fetch bank analysis data
        $topBanquesVente = $transactionsRepository->getTopBanksByTypeForPeriod('VENTE', $dateDebut, $dateFin, 10);
        $topBanquesAchat = $transactionsRepository->getTopBanksByTypeForPeriod('ACHAT', $dateDebut, $dateFin, 10);
        $bankSummary = $transactionsRepository->getBankSummaryForPeriod($dateDebut, $dateFin);

        // Calculate totals
        $totalVolumeVente = array_sum(array_map(fn($b) => (float) $b['volumeTotalUsd'], $topBanquesVente));
        $totalVolumeAchat = array_sum(array_map(fn($b) => (float) $b['volumeTotalUsd'], $topBanquesAchat));
        $totalVolumeGlobal = $totalVolumeVente + $totalVolumeAchat;
        $nbBanquesActives = count($bankSummary);

        // Top 1 banks
        $topVendeur = !empty($topBanquesVente) ? $topBanquesVente[0] : null;
        $topAcheteur = !empty($topBanquesAchat) ? $topBanquesAchat[0] : null;

        return $this->render('banques_analyse/index.html.twig', [
            'topBanquesVente' => $topBanquesVente,
            'topBanquesAchat' => $topBanquesAchat,
            'bankSummary' => $bankSummary,
            'totalVolumeVente' => $totalVolumeVente,
            'totalVolumeAchat' => $totalVolumeAchat,
            'totalVolumeGlobal' => $totalVolumeGlobal,
            'nbBanquesActives' => $nbBanquesActives,
            'topVendeur' => $topVendeur,
            'topAcheteur' => $topAcheteur,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'periode' => $periode,
        ]);
    }
}
