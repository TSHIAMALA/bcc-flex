<?php

namespace App\Controller;

use App\Repository\ConjonctureJourRepository;
use App\Repository\MarcheChangesRepository;
use App\Repository\ReservesFinancieresRepository;
use App\Repository\EncoursBccRepository;
use App\Repository\FinancesPubliquesRepository;
use App\Repository\ParametreGlobalRepository;
use App\Repository\TauxDirecteurRepository;
use App\Service\SlideExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page Indicateurs de Période — agrégats libres + pilotage Taux Directeur.
 * Permet de sélectionner une période avec bornes de dates libres.
 */
class IndicateursPeriodeController extends AbstractController
{
    #[Route('/', name: 'app_indicateurs_periode')]
    public function index(
        Request $request,
        ConjonctureJourRepository $conjonctureRepo,
        MarcheChangesRepository $marcheRepo,
        ReservesFinancieresRepository $reservesRepo,
        EncoursBccRepository $encoursRepo,
        FinancesPubliquesRepository $financesRepo,
        ParametreGlobalRepository $paramRepo,
        TauxDirecteurRepository $tauxRepo
    ): Response {
        // ── Bornes de période par défaut : 90 derniers jours ──────────────
        $latest = $conjonctureRepo->findLatest();
        $defaultEnd = $latest ? $latest->getDateSituation()->format('Y-m-d') : date('Y-m-d');
        $defaultStart = (new \DateTime($defaultEnd))->modify('-89 days')->format('Y-m-d');

        $dateDebut = $request->query->get('dateDebut', $defaultStart);
        $dateFin = $request->query->get('dateFin', $defaultEnd);

        // Normaliser si besoin
        if ($dateDebut > $dateFin) {
            [$dateDebut, $dateFin] = [$dateFin, $dateDebut];
        }

        // ── Agrégats ─────────────────────────────────────────────────────
        $change = $marcheRepo->getPeriodAggregates($dateDebut, $dateFin);
        $reserves = $reservesRepo->getPeriodAggregates($dateDebut, $dateFin);
        $encours = $encoursRepo->getPeriodAggregates($dateDebut, $dateFin);
        $finances = $financesRepo->getPeriodAggregates($dateDebut, $dateFin);

        // ── Taux Directeur (Historique structuré) ─────────────────────────
        $activeRate = $tauxRepo->findActiveRateAt(new \DateTime($dateFin));
        $tauxDirecteur = $activeRate ? (float) $activeRate->getValeur() : 25.0;

        $prevRate = $activeRate ? $tauxRepo->findPreviousRate($activeRate->getDateApplication()) : null;
        $tauxDirecteurPrec = $prevRate ? (float) $prevRate->getValeur() : $tauxDirecteur;

        // ── Calcul des signaux de la période ─────────────────────────────
        $ecartMoy = isset($change['ecart_pct_moy']) ? (float) $change['ecart_pct_moy'] : null;
        $ecartMax = isset($change['ecart_pct_max']) ? (float) $change['ecart_pct_max'] : null;
        $avLibMoy = isset($reserves['avoirs_libres_moy']) ? (float) $reserves['avoirs_libres_moy'] : null;
        $avLibMax = isset($reserves['avoirs_libres_max']) ? (float) $reserves['avoirs_libres_max'] : null;
        $reservesIntMoy = isset($reserves['reserves_int_moy']) ? (float) $reserves['reserves_int_moy'] : null;
        $soldeMoy = isset($finances['solde_moy']) ? (float) $finances['solde_moy'] : null;
        $sterilMoy = isset($encours['sterilisation_totale_moy']) ? (float) $encours['sterilisation_totale_moy'] : null;

        $signalChange = $this->computeSignalChange($ecartMoy, $ecartMax);
        $signalLiq = $this->computeSignalLiquidite($avLibMax);
        $signalTreso = $this->computeSignalTresorerie($soldeMoy);
        $signalReserves = $this->computeSignalReserves($reservesIntMoy);
        $signalGlobal = $this->pireSignal([$signalChange, $signalLiq, $signalTreso, $signalReserves]);

        // ── Recommandation Taux Directeur ─────────────────────────────────
        $recoTaux = $this->recommandationTauxDirecteur(
            $tauxDirecteur,
            $ecartMoy,
            $avLibMax,
            $soldeMoy
        );

        // ── Données pour les graphiques (évolution journalière) ───────────
        $evolutionChange = $marcheRepo->getEvolutionDataByPeriod($dateDebut, $dateFin);
        $evolutionReserves = $reservesRepo->getReservesHistoryByPeriod($dateDebut, $dateFin);
        $evolutionFinances = $financesRepo->getEvolutionDataByPeriod($dateDebut, $dateFin);

        // ── Préparer les données de graphiques (PHP → JSON propre) ────────
        $chartChange = [];
        foreach ($evolutionChange as $r) {
            $cj = $r->getConjoncture();
            $ci = $r->getCoursIndicatif() !== null ? (float) $r->getCoursIndicatif() : null;
            $pa = $r->getParalleleAchat() !== null ? (float) $r->getParalleleAchat() : null;
            $pv = $r->getParalleleVente() !== null ? (float) $r->getParalleleVente() : null;
            $mid = ($pa !== null && $pv !== null) ? ($pa + $pv) / 2.0 : null;
            $ecart = ($mid !== null && $ci !== null && $ci > 0) ? round(($mid - $ci) / $ci * 100, 2) : null;
            $chartChange[] = [
                'date' => $cj?->getDateSituation()?->format('d/m') ?? '',
                'ecart' => $ecart,
            ];
        }

        $chartReserves = [];
        foreach ($evolutionReserves as $r) {
            $cj = $r->getConjoncture();
            $ri = $r->getReservesInternationalesUsd() !== null ? round((float) $r->getReservesInternationalesUsd() / 1000, 2) : null;
            $av = $r->getAvoirsLibresCdf() !== null ? round((float) $r->getAvoirsLibresCdf(), 0) : null;
            $chartReserves[] = [
                'date' => $cj?->getDateSituation()?->format('d/m') ?? '',
                'res' => $ri,
                'av' => $av,
            ];
        }

        $chartFinances = [];
        foreach ($evolutionFinances as $r) {
            $cj = $r->getConjoncture();
            $chartFinances[] = [
                'date' => $cj?->getDateSituation()?->format('d/m') ?? '',
                'solde' => $r->getSolde() !== null ? round((float) $r->getSolde(), 0) : null,
            ];
        }

        return $this->render('indicateurs_periode/index.html.twig', [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            // agrégats bruts
            'change' => $change,
            'reserves' => $reserves,
            'encours' => $encours,
            'finances' => $finances,
            // signaux
            'signalChange' => $signalChange,
            'signalLiquidite' => $signalLiq,
            'signalTresorerie' => $signalTreso,
            'signalReserves' => $signalReserves,
            'signalGlobal' => $signalGlobal,
            // taux directeur
            'tauxDirecteur' => $tauxDirecteur,
            'tauxDirecteurPrec' => $tauxDirecteurPrec,
            'recoTaux' => $recoTaux,
            // valeurs calculées pour l'affichage
            'ecartMoy' => $ecartMoy,
            'ecartMax' => $ecartMax,
            'ecartMin' => isset($change['ecart_pct_min']) ? (float) $change['ecart_pct_min'] : null,
            'avLibresMoy' => $avLibMoy,
            'avLibresMax' => $avLibMax,
            'soldeMoy' => $soldeMoy,
            'sterilisationMoy' => $sterilMoy,
            'reservesIntMoy' => isset($reserves['reserves_int_moy']) ? (float) $reserves['reserves_int_moy'] : null,
            // données graphiques pré-calculées
            'chartChange' => $chartChange,
            'chartReserves' => $chartReserves,
            'chartFinances' => $chartFinances,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // EXPORT PPTX
    // ─────────────────────────────────────────────────────────────────────
    #[Route('/indicateurs-periode/export-slide', name: 'app_indicateurs_periode_slide')]
    public function exportSlide(
        Request $request,
        MarcheChangesRepository $marcheRepo,
        ReservesFinancieresRepository $reservesRepo,
        EncoursBccRepository $encoursRepo,
        FinancesPubliquesRepository $financesRepo,
        ParametreGlobalRepository $paramRepo,
        TauxDirecteurRepository $tauxRepo,
        ConjonctureJourRepository $conjonctureRepo,
        SlideExportService $slideService
    ): Response {
        $latest = $conjonctureRepo->findLatest();
        $defaultEnd = $latest ? $latest->getDateSituation()->format('Y-m-d') : date('Y-m-d');
        $defaultStart = (new \DateTime($defaultEnd))->modify('-89 days')->format('Y-m-d');

        $dateDebut = $request->query->get('dateDebut', $defaultStart);
        $dateFin = $request->query->get('dateFin', $defaultEnd);

        if ($dateDebut > $dateFin) {
            [$dateDebut, $dateFin] = [$dateFin, $dateDebut];
        }

        $change = $marcheRepo->getPeriodAggregates($dateDebut, $dateFin);
        $reserves = $reservesRepo->getPeriodAggregates($dateDebut, $dateFin);
        $encours = $encoursRepo->getPeriodAggregates($dateDebut, $dateFin);
        $finances = $financesRepo->getPeriodAggregates($dateDebut, $dateFin);

        $activeRate = $tauxRepo->findActiveRateAt(new \DateTime($dateFin));
        $tauxDirecteur = $activeRate ? (float) $activeRate->getValeur() : 25.0;

        $prevRate = $activeRate ? $tauxRepo->findPreviousRate($activeRate->getDateApplication()) : null;
        $tauxDirecteurPrec = $prevRate ? (float) $prevRate->getValeur() : $tauxDirecteur;

        $ecartMoy = isset($change['ecart_pct_moy']) ? (float) $change['ecart_pct_moy'] : null;
        $ecartMax = isset($change['ecart_pct_max']) ? (float) $change['ecart_pct_max'] : null;
        $avLibMax = isset($reserves['avoirs_libres_max']) ? (float) $reserves['avoirs_libres_max'] : null;
        $soldeMoy = isset($finances['solde_moy']) ? (float) $finances['solde_moy'] : null;
        $reservesIntMoy = isset($reserves['reserves_int_moy']) ? (float) $reserves['reserves_int_moy'] : null;

        $signalChange = $this->computeSignalChange($ecartMoy, $ecartMax);
        $signalLiq = $this->computeSignalLiquidite($avLibMax);
        $signalTreso = $this->computeSignalTresorerie($soldeMoy);
        $signalReserves = $this->computeSignalReserves($reserves['reserves_int_moy'] ?? null);
        $signalGlobal = $this->pireSignal([$signalChange, $signalLiq, $signalTreso, $signalReserves]);
        $recoTaux = $this->recommandationTauxDirecteur($tauxDirecteur, $ecartMoy, $avLibMax, $soldeMoy);

        $data = [
            'dateDebut' => new \DateTime($dateDebut),
            'dateFin' => new \DateTime($dateFin),
            'change' => $change,
            'reserves' => $reserves,
            'encours' => $encours,
            'finances' => $finances,
            'ecartMoy' => $ecartMoy,
            'ecartMax' => $ecartMax,
            'ecartMin' => isset($change['ecart_pct_min']) ? (float) $change['ecart_pct_min'] : null,
            'avLibresMoy' => isset($reserves['avoirs_libres_moy']) ? (float) $reserves['avoirs_libres_moy'] : null,
            'avLibresMax' => $avLibMax,
            'soldeMoy' => $soldeMoy,
            'sterilisationMoy' => isset($encours['sterilisation_totale_moy']) ? (float) $encours['sterilisation_totale_moy'] : null,
            'reservesIntMoy' => isset($reserves['reserves_int_moy']) ? (float) $reserves['reserves_int_moy'] : null,
            'tauxDirecteur' => $tauxDirecteur,
            'tauxDirecteurPrec' => $tauxDirecteurPrec,
            'signalChange' => $signalChange,
            'signalLiquidite' => $signalLiq,
            'signalTresorerie' => $signalTreso,
            'signalReserves' => $signalReserves,
            'signalGlobal' => $signalGlobal,
            'recoTaux' => $recoTaux,
        ];

        $tmpFile = $slideService->generatePolitiqueMonetaire($data);

        $periodLabel = str_replace('-', '', $dateDebut) . '_' . str_replace('-', '', $dateFin);
        $filename = "BCC_Politique_Monetaire_{$periodLabel}.pptx";

        $response = new Response(file_get_contents($tmpFile));
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");
        @unlink($tmpFile);

        return $response;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers signaux
    // ─────────────────────────────────────────────────────────────────────

    private function computeSignalChange(?float $ecartMoy, ?float $ecartMax): string
    {
        if ($ecartMoy === null)
            return 'secondary';
        if ($ecartMoy > 5.0 || ($ecartMax !== null && $ecartMax > 6.0))
            return 'red';
        if ($ecartMoy > 3.0 || ($ecartMax !== null && $ecartMax > 3.0))
            return 'orange';
        if ($ecartMoy > 2.0)
            return 'yellow';
        return 'green';
    }

    private function computeSignalLiquidite(?float $avLibresMax): string
    {
        if ($avLibresMax === null)
            return 'secondary';
        if ($avLibresMax > 1200)
            return 'red';
        if ($avLibresMax > 800)
            return 'orange';
        return 'green';
    }

    private function computeSignalReserves(?float $reservesIntMoy): string
    {
        if ($reservesIntMoy === null)
            return 'secondary';
        if ($reservesIntMoy < 2500)
            return 'red';
        if ($reservesIntMoy < 4000)
            return 'orange';
        if ($reservesIntMoy < 5000)
            return 'yellow';
        return 'green';
    }

    private function computeSignalTresorerie(?float $soldeMoy): string
    {
        if ($soldeMoy === null)
            return 'secondary';
        if ($soldeMoy < -100)
            return 'red';
        if ($soldeMoy < 0)
            return 'yellow';
        return 'green';
    }

    private function pireSignal(array $signaux): string
    {
        $ordre = ['red' => 4, 'orange' => 3, 'yellow' => 2, 'green' => 1, 'secondary' => 0];
        $pire = 'secondary';
        foreach ($signaux as $s) {
            if (($ordre[$s] ?? 0) > ($ordre[$pire] ?? 0)) {
                $pire = $s;
            }
        }
        return $pire;
    }

    /**
     * Recommandation de politique monétaire sur le taux directeur.
     * Retourne un tableau avec : action, label, justification, classe CSS.
     */
    private function recommandationTauxDirecteur(
        float $tauxActuel,
        ?float $ecartMoy,
        ?float $avLibresMax,
        ?float $soldeMoy
    ): array {
        $raisons = [];
        $pressions = 0;

        // Pilier 1 : change
        if ($ecartMoy !== null) {
            if ($ecartMoy > 5.0) {
                $raisons[] = sprintf("L'écart de change moyen est critique (%.1f%% > 5%%) — tout signal accommodant serait immédiatement transmis au marché parallèle et creuserait davantage la prime de change", $ecartMoy);
                $pressions += 2;
            } elseif ($ecartMoy > 3.0) {
                $raisons[] = sprintf("L'écart de change moyen de %.1f%% se situe en zone orange — la marge de tolérance est épuisée et un signal d'assouplissement serait contre-productif", $ecartMoy);
                $pressions += 1;
            } elseif ($ecartMoy <= 2.0) {
                $raisons[] = sprintf("L'écart de change est maîtrisé (%.1f%% < 2%%) — le marché valide l'alignement du taux indicatif sur le parallèle", $ecartMoy);
                $pressions -= 1;
            }
        }

        // Pilier 3 : liquidité
        if ($avLibresMax !== null) {
            if ($avLibresMax > 1200) {
                $raisons[] = sprintf("La surliquidité bancaire est structurellement excessive (avoirs libres max : %.0f Mds CDF > 1 200 Mds) — elle constitue un carburant latent pour la demande de devises dès le moindre signal accommodant", $avLibresMax);
                $pressions += 1;
            } elseif ($avLibresMax < 500) {
                $raisons[] = sprintf("La liquidité bancaire est contrainte (%.0f Mds CDF), signalant un risque de contraction du crédit — une détente partielle serait justifiée si le change le permet", $avLibresMax);
                $pressions -= 1;
            }
        }

        // Pilier 4 : trésorerie
        if ($soldeMoy !== null) {
            if ($soldeMoy < -100) {
                $raisons[] = sprintf("Le solde de trésorerie de l'État est déficitaire (%.0f Mds CDF en moyenne) — ce déficit crée un risque de recours monétaire et réduit la marge de coordination BCC-Trésor", $soldeMoy);
                $pressions += 1;
            }
        }

        if ($pressions >= 2) {
            return [
                'action' => 'MAINTENIR',
                'label' => 'Maintien impératif — Posture restrictive',
                'taux' => $tauxActuel,
                'classe' => 'warning',
                'emoji' => '🔒',
                'justification' => implode('. ', $raisons)
                    . sprintf('. Le maintien du taux directeur à %.1f%% est indispensable pour ancrer les anticipations inflationnistes et crédibiliser la politique de change.', $tauxActuel),
            ];
        }

        if ($pressions >= 1) {
            return [
                'action' => 'VIGILANCE',
                'label' => 'Neutralité restrictive — Abstention de toute baisse',
                'taux' => $tauxActuel,
                'classe' => 'info',
                'emoji' => '⚠️',
                'justification' => implode('. ', $raisons)
                    . sprintf(' Le taux directeur à %.1f%% doit demeurer inchangé. Tout signal accommodant dans ce contexte serait interprété comme un relâchement et se transmettrait immédiatement au marché parallèle.', $tauxActuel),
            ];
        }

        if ($pressions <= -1) {
            return [
                'action' => 'BAISSER',
                'label' => 'Assouplissement prudent — Conditions favorables réunies',
                'taux' => $tauxActuel,
                'classe' => 'success',
                'emoji' => '📉',
                'justification' => implode('. ', $raisons)
                    . sprintf(' Les conditions permettent d\'envisager un assouplissement graduel et contrôlé du taux directeur (actuellement %.1f%%), à condition que la communication soit calibrée pour ne pas raviver les anticipations de dépréciation.', $tauxActuel),
            ];
        }

        return [
            'action' => 'STATU QUO',
            'label' => 'Neutralité active — Surveillance continue',
            'taux' => $tauxActuel,
            'classe' => 'secondary',
            'emoji' => '📊',
            'justification' => 'Les indicateurs sont globalement dans les normes. '
                . sprintf('Le taux directeur (%.1f%%) peut demeurer inchangé. La disponibilité à agir en cas de dégradation des indicateurs devra être signalée clairement au marché.', $tauxActuel),
        ];
    }
}
