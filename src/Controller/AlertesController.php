<?php

namespace App\Controller;

use App\Repository\RegleInterventionRepository;
use App\Service\AlerteService;
use App\Service\IndiceTensionService;
use App\Repository\ConjonctureJourRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlertesController extends AbstractController
{
    #[Route('/alertes', name: 'app_alertes')]
    public function index(
        Request $request,
        AlerteService $alerteService,
        IndiceTensionService $itmService,
        RegleInterventionRepository $regleRepository,
        ConjonctureJourRepository $conjonctureRepository
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
                default: // 30jours
                    $dateDebut = date('Y-m-d', strtotime('-30 days'));
            }
        }

        // Get alerts filtered by period
        $activeAlerts = $alerteService->getFormattedAlertsByPeriod($dateDebut, $dateFin);
        
        // Get all configured rules
        $regles = $regleRepository->findAllWithIndicateurs();
        
        // Get latest conjoncture within the period for ITM
        $latestConjoncture = $conjonctureRepository->findLatestByPeriod($dateDebut, $dateFin);
        $itm = $itmService->calculateITM($latestConjoncture);

        // Format rules for display with values from the period
        $formattedRules = [];
        foreach ($regles as $regle) {
            $indicateur = $regle->getIndicateur();
            $value = $latestConjoncture 
                ? $alerteService->getIndicatorValue($indicateur, $latestConjoncture) 
                : null;
            
            $status = $value !== null 
                ? $alerteService->getAlertStatus($value, $regle) 
                : 'N/A';
            
            $formattedRules[] = [
                'indicateur' => $indicateur->getLibelle(),
                'code' => $indicateur->getCode(),
                'unite' => $indicateur->getUnite(),
                'seuilVigilance' => $regle->getSeuilAlerte(),
                'seuilIntervention' => $regle->getSeuilIntervention(),
                'sens' => $regle->getSens(),
                'poids' => $regle->getPoids(),
                'valeurActuelle' => $value,
                'statut' => $status,
            ];
        }

        // Count conjonctures in period
        $conjoncturesPeriode = $conjonctureRepository->findByPeriod($dateDebut, $dateFin);

        return $this->render('alertes/index.html.twig', [
            'alertes' => $activeAlerts,
            'regles' => $formattedRules,
            'itm' => $itm,
            'alertCount' => count(array_filter($activeAlerts, fn($a) => $a['statut'] !== 'NORMAL')),
            'ruleCount' => count($regles),
            'periode' => $periode,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'joursAnalyses' => count($conjoncturesPeriode),
        ]);
    }

    #[Route('/alertes/recalculate', name: 'app_alertes_recalculate', methods: ['POST'])]
    public function recalculate(
        Request $request,
        AlerteService $alerteService,
        ConjonctureJourRepository $conjonctureRepository
    ): Response {
        // Get period from request for targeted recalculation
        $dateDebut = $request->request->get('dateDebut');
        $dateFin = $request->request->get('dateFin');

        if ($dateDebut && $dateFin) {
            $conjonctures = $conjonctureRepository->findByPeriod($dateDebut, $dateFin);
            $count = 0;
            foreach ($conjonctures as $conjoncture) {
                $alerteService->calculateAlerts($conjoncture);
                $count++;
            }
            $this->addFlash('success', "Alertes recalculées sur $count jour(s).");
        } else {
            $latestConjoncture = $conjonctureRepository->findLatest();
            if ($latestConjoncture) {
                $alerteService->calculateAlerts($latestConjoncture);
                $this->addFlash('success', 'Alertes recalculées avec succès.');
            } else {
                $this->addFlash('warning', 'Aucune conjoncture trouvée.');
            }
        }
        
        return $this->redirectToRoute('app_alertes', [
            'periode' => $request->request->get('periode', '30jours'),
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ]);
    }
}
