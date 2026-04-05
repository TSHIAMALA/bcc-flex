<?php

namespace App\Controller;

use App\Repository\ConjonctureJourRepository;
use App\Repository\EncoursBccRepository;
use App\Repository\FinancesPubliquesRepository;
use App\Repository\MarcheChangesRepository;
use App\Repository\PaieEtatRepository;
use App\Repository\ReservesFinancieresRepository;
use App\Repository\TauxDirecteurRepository;
use App\Repository\TresorerieEtatRepository;
use App\Service\IndicateursCalculService;
use App\Service\SlideExportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FicheJournaliereController extends AbstractController
{
    #[Route('/fiche-journaliere', name: 'app_fiche_journaliere')]
    public function index(
        Request $request,
        ConjonctureJourRepository $conjonctureRepo,
        MarcheChangesRepository $marcheRepo,
        ReservesFinancieresRepository $reservesRepo,
        EncoursBccRepository $encoursRepo,
        FinancesPubliquesRepository $financesRepo,
        TresorerieEtatRepository $tresorerieRepo,
        PaieEtatRepository $paieRepo,
        TauxDirecteurRepository $tauxRepo,
        IndicateursCalculService $calc
    ): Response {
        $dateStr = $request->query->get('date');
        if ($dateStr) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $dateStr);
        } else {
            $latest = $conjonctureRepo->findLatest();
            $dateObj = $latest ? clone $latest->getDateSituation() : new \DateTime();
        }

        $conjoncture = $conjonctureRepo->findOneBy(['date_situation' => $dateObj]);

        $marche = $conjoncture ? $marcheRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $reserves = $conjoncture ? $reservesRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $encours = $conjoncture ? $encoursRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $finances = $conjoncture ? $financesRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $tresorerie = $conjoncture ? $tresorerieRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $paie = $conjoncture ? $paieRepo->findOneBy(['conjoncture' => $conjoncture]) : null;

        $ecartPct = $calc->getEcartPct($marche);
        $ecartMaxPct = $calc->getEcartMaxPct($marche);
        $spreadPct = $calc->getSpreadParallelePct($marche);
        $midParallele = $calc->getMidParallele($marche);
        $ratioEncoursBons = $calc->getRatioEncoursBons($encours, $reserves);
        $totalEncours = $calc->getTotalEncoursBons($encours);
        $tauxPaie = $calc->getTauxExecutionPaie($paie);
        $pctRestePaie = $calc->getPctRestePayie($paie);

        $tauxInterbancaire = $encours ? $encours->getTauxInterbancaire() : null;
        $tauxMoyenPondere = $encours ? $encours->getTauxMoyenPondereBbcc() : null;
        $billetsEnCirculation = $encours ? $encours->getBilletsEnCirculation() : null;
        
        $activeRate = $tauxRepo->findActiveRateAt($dateObj);
        $tauxDirecteur = $activeRate ? (float) $activeRate->getValeur() : null;
        $dateTauxDirecteur = $activeRate ? $activeRate->getDateApplication() : null;

        // CGT : variation J vs J-1
        $prevConj = $conjonctureRepo->findPreviousTo($dateObj);
        $tresorerieJm1 = $prevConj ? $tresorerieRepo->findOneBy(['conjoncture' => $prevConj]) : null;
        $impactCgt = $calc->getImpactCgtSurLiquidite($tresorerie, $tresorerieJm1);
        $variationCgt = $impactCgt ? $impactCgt['variation'] : null;
        $soldeCgt = $impactCgt ? $impactCgt['soldeCgt'] : null;

        $signalChange = $calc->getSignalChange($marche);
        $signalMarcheMonetaire = $calc->getSignalMarcheMonetaire($encours, $reserves);
        $signalLiquidite = $calc->getSignalLiquidite($reserves);
        $signalTresorerie = $calc->getSignalTresorerie($tresorerie);
        $signalPaie = $calc->getSignalPaie($paie);

        $signalReserves = 'secondary';
        if ($reserves && $reserves->getReservesInternationalesUsd() !== null) {
            $r = (float) $reserves->getReservesInternationalesUsd();
            $signalReserves = $r >= 5000 ? 'green' : ($r >= 3000 ? 'yellow' : 'red');
        }

        $scenario = $calc->getScenarioPilotage($marche, $reserves, $tresorerie, $paie);
        $phraseCabinet = $calc->getPhraseCabinet($marche, $reserves, $tresorerie, $paie, $scenario);

        $colorRank = ['secondary' => 0, 'green' => 1, 'yellow' => 2, 'orange' => 3, 'red' => 4];
        $worstSignal = 'secondary';
        foreach ([$signalChange, $signalLiquidite, $signalTresorerie, $signalPaie] as $s) {
            if (($colorRank[$s] ?? 0) > ($colorRank[$worstSignal] ?? 0)) {
                $worstSignal = $s;
            }
        }

        $availableDates = $conjonctureRepo->findBy([], ['date_situation' => 'DESC'], 30);

        $viewData = [
            'date' => $dateObj,
            'conjoncture' => $conjoncture,
            'marche' => $marche,
            'reserves' => $reserves,
            'encours' => $encours,
            'finances' => $finances,
            'tresorerie' => $tresorerie,
            'paie' => $paie,
            'ecartPct' => $ecartPct,
            'ecartMaxPct' => $ecartMaxPct,
            'spreadPct' => $spreadPct,
            'midParallele' => $midParallele,
            'ratioEncoursBons' => $ratioEncoursBons,
            'totalEncours' => $totalEncours,
            'tauxInterbancaire' => $tauxInterbancaire,
            'tauxMoyenPondere' => $tauxMoyenPondere,
            'billetsEnCirculation' => $billetsEnCirculation,
            'tauxDirecteur' => $tauxDirecteur,
            'dateTauxDirecteur' => $dateTauxDirecteur,
            'tauxPaie' => $tauxPaie,
            'pctRestePaie' => $pctRestePaie,
            'signalChange' => $signalChange,
            'signalLiquidite' => $signalLiquidite,
            'signalMarcheMonetaire' => $signalMarcheMonetaire,
            'signalTresorerie' => $signalTresorerie,
            'signalPaie' => $signalPaie,
            'signalReserves' => $signalReserves,
            'worstSignal' => $worstSignal,
            'scenario' => $scenario,
            'phraseCabinet' => $phraseCabinet,
            'availableDates' => $availableDates,
            // CGT
            'soldeCgt' => $soldeCgt,
            'variationCgt' => $variationCgt,
            'impactCgt' => $impactCgt,
        ];

        return $this->render('fiche/index.html.twig', $viewData);
    }

    #[Route('/fiche-journaliere/pdf', name: 'app_fiche_journaliere_pdf')]
    public function pdf(
        Request $request,
        ConjonctureJourRepository $conjonctureRepo,
        MarcheChangesRepository $marcheRepo,
        ReservesFinancieresRepository $reservesRepo,
        EncoursBccRepository $encoursRepo,
        FinancesPubliquesRepository $financesRepo,
        TresorerieEtatRepository $tresorerieRepo,
        PaieEtatRepository $paieRepo,
        TauxDirecteurRepository $tauxRepo,
        IndicateursCalculService $calc
    ): Response {
        $dateStr = $request->query->get('date');
        $dateObj = $dateStr
            ? \DateTime::createFromFormat('Y-m-d', $dateStr)
            : (($latest = $conjonctureRepo->findLatest()) ? clone $latest->getDateSituation() : new \DateTime());

        $conjoncture = $conjonctureRepo->findOneBy(['date_situation' => $dateObj]);

        $marche = $conjoncture ? $marcheRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $reserves = $conjoncture ? $reservesRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $encours = $conjoncture ? $encoursRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $finances = $conjoncture ? $financesRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $tresorerie = $conjoncture ? $tresorerieRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $paie = $conjoncture ? $paieRepo->findOneBy(['conjoncture' => $conjoncture]) : null;

        $ecartPct = $calc->getEcartPct($marche);
        $ecartMaxPct = $calc->getEcartMaxPct($marche);
        $ratioEncoursBons = $calc->getRatioEncoursBons($encours, $reserves);
        $totalEncours = $calc->getTotalEncoursBons($encours);
        $tauxInterbancaire = $encours ? $encours->getTauxInterbancaire() : null;
        $tauxMoyenPondere = $encours ? $encours->getTauxMoyenPondereBbcc() : null;
        $billetsEnCirculation = $encours ? $encours->getBilletsEnCirculation() : null;
        
        $activeRate = $tauxRepo->findActiveRateAt($dateObj);
        $tauxDirecteur = $activeRate ? (float) $activeRate->getValeur() : null;
        $dateTauxDirecteur = $activeRate ? $activeRate->getDateApplication() : null;

        // CGT J-1
        $prevConj = $conjonctureRepo->findPreviousTo($dateObj);
        $tresorerieJm1 = $prevConj ? $tresorerieRepo->findOneBy(['conjoncture' => $prevConj]) : null;
        $impactCgt = $calc->getImpactCgtSurLiquidite($tresorerie, $tresorerieJm1);
        $variationCgt = $impactCgt ? $impactCgt['variation'] : null;
        $soldeCgt = $impactCgt ? $impactCgt['soldeCgt'] : null;

        $tauxPaie = $calc->getTauxExecutionPaie($paie);
        $pctRestePaie = $calc->getPctRestePayie($paie);
        $scenario = $calc->getScenarioPilotage($marche, $reserves, $tresorerie, $paie);
        $phraseCabinet = $calc->getPhraseCabinet($marche, $reserves, $tresorerie, $paie, $scenario);

        $signalChange = $calc->getSignalChange($marche);
        $signalMarcheMonetaire = $calc->getSignalMarcheMonetaire($encours, $reserves);
        $signalLiquidite = $calc->getSignalLiquidite($reserves);
        $signalTresorerie = $calc->getSignalTresorerie($tresorerie);
        $signalPaie = $calc->getSignalPaie($paie);

        $html = $this->renderView('fiche/pdf.html.twig', [
            'date' => $dateObj,
            'marche' => $marche,
            'reserves' => $reserves,
            'encours' => $encours,
            'finances' => $finances,
            'tresorerie' => $tresorerie,
            'paie' => $paie,
            'ecartPct' => $ecartPct,
            'ecartMaxPct' => $ecartMaxPct,
            'ratioEncoursBons' => $ratioEncoursBons,
            'totalEncours' => $totalEncours,
            'tauxInterbancaire' => $tauxInterbancaire,
            'tauxMoyenPondere' => $tauxMoyenPondere,
            'billetsEnCirculation' => $billetsEnCirculation,
            'tauxDirecteur' => $tauxDirecteur,
            'dateTauxDirecteur' => $dateTauxDirecteur,
            'tauxPaie' => $tauxPaie,
            'pctRestePaie' => $pctRestePaie,
            'signalChange' => $signalChange,
            'signalLiquidite' => $signalLiquidite,
            'signalMarcheMonetaire' => $signalMarcheMonetaire,
            'signalTresorerie' => $signalTresorerie,
            'signalPaie' => $signalPaie,
            'scenario' => $scenario,
            'phraseCabinet' => $phraseCabinet,
            'soldeCgt' => $soldeCgt,
            'variationCgt' => $variationCgt,
            'impactCgt' => $impactCgt,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaSans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'fiche-journaliere-' . $dateObj->format('Y-m-d') . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    #[Route('/fiche-journaliere/slides', name: 'app_fiche_journaliere_slides')]
    public function slides(
        Request $request,
        ConjonctureJourRepository $conjonctureRepo,
        MarcheChangesRepository $marcheRepo,
        ReservesFinancieresRepository $reservesRepo,
        EncoursBccRepository $encoursRepo,
        FinancesPubliquesRepository $financesRepo,
        TresorerieEtatRepository $tresorerieRepo,
        PaieEtatRepository $paieRepo,
        TauxDirecteurRepository $tauxRepo,
        IndicateursCalculService $calc,
        SlideExportService $slideService
    ): Response {
        $dateStr = $request->query->get('date');
        $dateObj = $dateStr
            ? \DateTime::createFromFormat('Y-m-d', $dateStr)
            : (($latest = $conjonctureRepo->findLatest()) ? clone $latest->getDateSituation() : new \DateTime());

        $conjoncture = $conjonctureRepo->findOneBy(['date_situation' => $dateObj]);

        $marche = $conjoncture ? $marcheRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $reserves = $conjoncture ? $reservesRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $encours = $conjoncture ? $encoursRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $finances = $conjoncture ? $financesRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $tresorerie = $conjoncture ? $tresorerieRepo->findOneBy(['conjoncture' => $conjoncture]) : null;
        $paie = $conjoncture ? $paieRepo->findOneBy(['conjoncture' => $conjoncture]) : null;

        $ecartPct = $calc->getEcartPct($marche);
        $ecartMaxPct = $calc->getEcartMaxPct($marche);
        $spreadPct = $calc->getSpreadParallelePct($marche);
        $ratioEncoursBons = $calc->getRatioEncoursBons($encours, $reserves);
        $totalEncours = $calc->getTotalEncoursBons($encours);
        
        $tauxInterbancaire = $encours ? $encours->getTauxInterbancaire() : null;
        $tauxMoyenPondere = $encours ? $encours->getTauxMoyenPondereBbcc() : null;
        $billetsEnCirculation = $encours ? $encours->getBilletsEnCirculation() : null;
        
        $activeRate = $tauxRepo->findActiveRateAt($dateObj);
        $tauxDirecteur = $activeRate ? (float) $activeRate->getValeur() : null;
        $dateTauxDirecteur = $activeRate ? $activeRate->getDateApplication() : null;

        // CGT J-1 (slides)
        $prevConj = $conjonctureRepo->findPreviousTo($dateObj);
        $tresorerieJm1 = $prevConj ? $tresorerieRepo->findOneBy(['conjoncture' => $prevConj]) : null;
        $impactCgt = $calc->getImpactCgtSurLiquidite($tresorerie, $tresorerieJm1);
        $variationCgt = $impactCgt ? $impactCgt['variation'] : null;
        $soldeCgt = $impactCgt ? $impactCgt['soldeCgt'] : null;

        $tauxPaie = $calc->getTauxExecutionPaie($paie);
        $pctRestePaie = $calc->getPctRestePayie($paie);

        $signalChange = $calc->getSignalChange($marche);
        $signalMarcheMonetaire = $calc->getSignalMarcheMonetaire($encours, $reserves);
        $signalLiquidite = $calc->getSignalLiquidite($reserves);
        $signalTresorerie = $calc->getSignalTresorerie($tresorerie);
        $signalPaie = $calc->getSignalPaie($paie);

        $signalReserves = 'secondary';
        if ($reserves && $reserves->getReservesInternationalesUsd() !== null) {
            $r = (float) $reserves->getReservesInternationalesUsd();
            $signalReserves = $r >= 5000 ? 'green' : ($r >= 3000 ? 'yellow' : 'red');
        }

        $colorRank = ['secondary' => 0, 'green' => 1, 'yellow' => 2, 'orange' => 3, 'red' => 4];
        $worstSignal = 'secondary';
        foreach ([$signalChange, $signalLiquidite, $signalTresorerie, $signalPaie] as $s) {
            if (($colorRank[$s] ?? 0) > ($colorRank[$worstSignal] ?? 0)) {
                $worstSignal = $s;
            }
        }

        $scenario = $calc->getScenarioPilotage($marche, $reserves, $tresorerie, $paie);
        $phraseCabinet = $calc->getPhraseCabinet($marche, $reserves, $tresorerie, $paie, $scenario);

        $data = [
            'date' => $dateObj,
            'marche' => $marche,
            'reserves' => $reserves,
            'encours' => $encours,
            'finances' => $finances,
            'tresorerie' => $tresorerie,
            'paie' => $paie,
            'ecartPct' => $ecartPct,
            'ecartMaxPct' => $ecartMaxPct,
            'spreadPct' => $spreadPct,
            'ratioEncoursBons' => $ratioEncoursBons,
            'ratioSteri'       => $ratioEncoursBons,
            'totalEncours'     => $totalEncours,
            'tauxInterbancaire' => $tauxInterbancaire,
            'tauxMoyenPondere' => $tauxMoyenPondere,
            'billetsEnCirculation' => $billetsEnCirculation,
            'tauxDirecteur' => $tauxDirecteur,
            'dateTauxDirecteur' => $dateTauxDirecteur,
            'tauxPaie' => $tauxPaie,
            'pctRestePaie' => $pctRestePaie,
            'signalChange' => $signalChange,
            'signalLiquidite' => $signalLiquidite,
            'signalMarcheMonetaire' => $signalMarcheMonetaire,
            'signalTresorerie' => $signalTresorerie,
            'signalPaie' => $signalPaie,
            'signalReserves' => $signalReserves,
            'worstSignal' => $worstSignal,
            'scenario' => $scenario,
            'phraseCabinet' => $phraseCabinet,
            // CGT
            'soldeCgt' => $soldeCgt,
            'variationCgt' => $variationCgt,
            'impactCgt' => $impactCgt,
        ];

        $tmpFile = $slideService->generate($data);
        $filename = 'fiche-journaliere-' . $dateObj->format('Y-m-d') . '.pptx';

        $response = new Response(file_get_contents($tmpFile), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);

        @unlink($tmpFile);

        return $response;
    }
}
