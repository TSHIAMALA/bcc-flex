<?php

namespace App\Controller;

use App\Repository\RegleInterventionRepository;
use App\Service\AlerteService;
use App\Service\IndiceTensionService;
use App\Repository\ConjonctureJourRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlertesController extends AbstractController
{
    #[Route('/alertes', name: 'app_alertes')]
    public function index(
        AlerteService $alerteService,
        IndiceTensionService $itmService,
        RegleInterventionRepository $regleRepository,
        ConjonctureJourRepository $conjonctureRepository
    ): Response {
        // Get active alerts from database
        $activeAlerts = $alerteService->getFormattedAlerts();
        
        // Get all configured rules
        $regles = $regleRepository->findAllWithIndicateurs();
        
        // Get latest conjoncture for ITM
        $latestConjoncture = $conjonctureRepository->findLatest();
        $itm = $itmService->calculateITM($latestConjoncture);

        // Format rules for display
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

        return $this->render('alertes/index.html.twig', [
            'alertes' => $activeAlerts,
            'regles' => $formattedRules,
            'itm' => $itm,
            'alertCount' => count(array_filter($activeAlerts, fn($a) => $a['statut'] !== 'NORMAL')),
            'ruleCount' => count($regles),
        ]);
    }

    #[Route('/alertes/recalculate', name: 'app_alertes_recalculate', methods: ['POST'])]
    public function recalculate(
        AlerteService $alerteService,
        ConjonctureJourRepository $conjonctureRepository
    ): Response {
        $latestConjoncture = $conjonctureRepository->findLatest();
        
        if ($latestConjoncture) {
            $alerteService->calculateAlerts($latestConjoncture);
            $this->addFlash('success', 'Alertes recalculées avec succès.');
        } else {
            $this->addFlash('warning', 'Aucune conjoncture trouvée.');
        }
        
        return $this->redirectToRoute('app_alertes');
    }
}
