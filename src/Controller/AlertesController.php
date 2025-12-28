<?php

namespace App\Controller;

use App\Repository\KPIJournalierRepository;
use App\Repository\MarcheChangesRepository;
use App\Repository\FinancesPubliquesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlertesController extends AbstractController
{
    #[Route('/alertes', name: 'app_alertes')]
    public function index(
        KPIJournalierRepository $kpiRepository,
        MarcheChangesRepository $marcheRepository,
        FinancesPubliquesRepository $financesRepository
    ): Response {
        $latestKPI = $kpiRepository->getLatestKPI();
        $latestMarche = $marcheRepository->findOneBy([], ['id' => 'DESC']);
        $latestFinances = $financesRepository->findOneBy([], ['id' => 'DESC']);

        $seuils = [
            'ecart_change' => 100,
            'reserves_min' => 5000,
            'deficit_max' => -200
        ];

        $alertes = [];

        if ($latestMarche && $latestMarche->getEcartIndicParallele() > $seuils['ecart_change']) {
            $alertes[] = [
                'type' => 'warning',
                'icon' => 'exchange-alt',
                'titre' => 'Écart de change élevé',
                'message' => sprintf('L\'écart indicatif/parallèle (%s CDF) dépasse le seuil de %s CDF', 
                    number_format($latestMarche->getEcartIndicParallele(), 0, ',', ' '), 
                    number_format($seuils['ecart_change'], 0, ',', ' ')),
                'date' => $latestMarche->getConjoncture()->getDateSituation()
            ];
        }

        if ($latestKPI && $latestKPI->getReservesInternationalesUsd() < $seuils['reserves_min']) {
            $alertes[] = [
                'type' => 'danger',
                'icon' => 'piggy-bank',
                'titre' => 'Réserves internationales basses',
                'message' => sprintf('Les réserves (%s Mio USD) sont inférieures au seuil de %s Mio USD',
                    number_format($latestKPI->getReservesInternationalesUsd(), 0, ',', ' '),
                    number_format($seuils['reserves_min'], 0, ',', ' ')),
                'date' => $latestKPI->getDateSituation()
            ];
        }

        if ($latestFinances && $latestFinances->getSolde() < $seuils['deficit_max']) {
            $alertes[] = [
                'type' => 'danger',
                'icon' => 'chart-line',
                'titre' => 'Déficit budgétaire critique',
                'message' => sprintf('Le déficit (%s Mds CDF) dépasse le seuil de %s Mds CDF',
                    number_format($latestFinances->getSolde(), 2, ',', ' '),
                    number_format($seuils['deficit_max'], 2, ',', ' ')),
                'date' => $latestFinances->getConjoncture()->getDateSituation()
            ];
        }

        return $this->render('alertes/index.html.twig', [
            'alertes' => $alertes,
            'seuils' => $seuils,
            'latestKPI' => $latestKPI,
            'latestMarche' => $latestMarche,
            'latestFinances' => $latestFinances,
        ]);
    }
}
