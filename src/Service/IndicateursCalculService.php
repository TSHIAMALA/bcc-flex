<?php

namespace App\Service;

use App\Entity\ConjonctureJour;
use App\Entity\EncoursBcc;
use App\Entity\FinancesPubliques;
use App\Entity\MarcheChanges;
use App\Entity\PaieEtat;
use App\Entity\ReservesFinancieres;
use App\Entity\TresorerieEtat;
use App\Repository\ParametreGlobalRepository;

/**
 * Service central de calcul de tous les indicateurs dérivés BCC-Flex.
 * Adapté pour lire les seuils d'alerte depuis la base de données (ParametreGlobal).
 */
class IndicateursCalculService
{
    private ParametreGlobalRepository $paramRepo;
    private array $params = [];

    public function __construct(ParametreGlobalRepository $paramRepo)
    {
        $this->paramRepo = $paramRepo;
        $this->loadParams();
    }

    private function loadParams(): void
    {
        $all = $this->paramRepo->findAll();
        foreach ($all as $p) {
            $this->params[$p->getCode()] = (float) $p->getValeur();
        }
    }

    private function getParam(string $code, float $default): float
    {
        return $this->params[$code] ?? $default;
    }

    // ─────────────────────────────────────────────────────────────────────
    // 1. MARCHÉ DES CHANGES
    // ─────────────────────────────────────────────────────────────────────

    public function getMidParallele(?MarcheChanges $marche): ?float
    {
        if (!$marche || $marche->getParalleleAchat() === null || $marche->getParalleleVente() === null) {
            return null;
        }
        return ((float) $marche->getParalleleAchat() + (float) $marche->getParalleleVente()) / 2.0;
    }

    public function getEcartAbsolu(?MarcheChanges $marche): ?float
    {
        $mid = $this->getMidParallele($marche);
        if ($mid === null || $marche->getCoursIndicatif() === null) {
            return null;
        }
        return $mid - (float) $marche->getCoursIndicatif();
    }

    public function getEcartPct(?MarcheChanges $marche): ?float
    {
        $indicatif = $marche && $marche->getCoursIndicatif() !== null ? (float) $marche->getCoursIndicatif() : null;
        $ecart = $this->getEcartAbsolu($marche);
        if ($indicatif === null || $indicatif == 0 || $ecart === null) {
            return null;
        }
        return ($ecart / $indicatif) * 100.0;
    }

    public function getEcartMaxPct(?MarcheChanges $marche): ?float
    {
        if (!$marche || $marche->getParalleleVente() === null || $marche->getCoursIndicatif() === null) {
            return null;
        }
        $indicatif = (float) $marche->getCoursIndicatif();
        if ($indicatif == 0)
            return null;
        return (((float) $marche->getParalleleVente() - $indicatif) / $indicatif) * 100.0;
    }

    public function getSpreadParallelePct(?MarcheChanges $marche): ?float
    {
        $mid = $this->getMidParallele($marche);
        if ($mid === null || $mid == 0 || $marche->getParalleleAchat() === null || $marche->getParalleleVente() === null) {
            return null;
        }
        return (((float) $marche->getParalleleVente() - (float) $marche->getParalleleAchat()) / $mid) * 100.0;
    }

    // ─────────────────────────────────────────────────────────────────────
    // 2. LIQUIDITÉ & STÉRILISATION
    // ─────────────────────────────────────────────────────────────────────

    public function getTotalSterilisation(?EncoursBcc $encours): ?float
    {
        if (!$encours)
            return null;
        $ot = $encours->getEncoursOtBcc() !== null ? (float) $encours->getEncoursOtBcc() : 0.0;
        $b = $encours->getEncoursBBcc() !== null ? (float) $encours->getEncoursBBcc() : 0.0;
        return $ot + $b;
    }

    public function getRatioSterilisation(?EncoursBcc $encours, ?ReservesFinancieres $reserves): ?float
    {
        $total = $this->getTotalSterilisation($encours);
        if ($total === null || !$reserves || $reserves->getAvoirsLibresCdf() === null)
            return null;
        $avoirsLibres = (float) $reserves->getAvoirsLibresCdf();
        if ($avoirsLibres == 0)
            return null;
        return $total / $avoirsLibres;
    }

    // ─────────────────────────────────────────────────────────────────────
    // 3. PAIE
    // ─────────────────────────────────────────────────────────────────────

    public function getTauxExecutionPaie(?PaieEtat $paie): ?float
    {
        if (!$paie || $paie->getMontantTotal() === null || (float) $paie->getMontantTotal() == 0) {
            return null;
        }
        $paye = $paie->getMontantPaye() !== null ? (float) $paie->getMontantPaye() : 0.0;
        return ($paye / (float) $paie->getMontantTotal()) * 100.0;
    }

    public function getPctRestePayie(?PaieEtat $paie): ?float
    {
        $exec = $this->getTauxExecutionPaie($paie);
        return $exec !== null ? 100.0 - $exec : null;
    }

    // ─────────────────────────────────────────────────────────────────────
    // 4. ALERTES & CODES COULEUR
    // ─────────────────────────────────────────────────────────────────────

    public function getSignalChange(?MarcheChanges $marche): string
    {
        $ecartPct = $this->getEcartPct($marche);
        $ecartMax = $this->getEcartMaxPct($marche);

        if ($ecartPct === null)
            return 'secondary';

        $seuilRouge = $this->getParam('SEUIL_CHANGE_ROUGE', 5.0);
        $seuilMaxRouge = $this->getParam('SEUIL_MAX_CHANGE_ROUGE', 6.0);
        $seuilOrange = $this->getParam('SEUIL_CHANGE_ORANGE', 3.0);
        $seuilMaxOrange = $this->getParam('SEUIL_MAX_CHANGE_ORANGE', 3.0);
        $seuilJaune = $this->getParam('SEUIL_CHANGE_JAUNE', 2.0);

        if ($ecartPct > $seuilRouge || ($ecartMax !== null && $ecartMax > $seuilMaxRouge))
            return 'red';
        if ($ecartPct > $seuilOrange || ($ecartMax !== null && $ecartMax > $seuilMaxOrange))
            return 'orange';
        if ($ecartPct > $seuilJaune)
            return 'yellow';

        return 'green';
    }

    public function getSignalLiquidite(?ReservesFinancieres $reserves): string
    {
        if (!$reserves || $reserves->getAvoirsLibresCdf() === null)
            return 'secondary';

        $av = (float) $reserves->getAvoirsLibresCdf();
        $seuilRouge = $this->getParam('SEUIL_LIQUIDITE_ROUGE', 1200);
        $seuilOrange = $this->getParam('SEUIL_LIQUIDITE_ORANGE', 800);

        if ($av > $seuilRouge)
            return 'red';
        if ($av > $seuilOrange)
            return 'orange';

        return 'green';
    }

    public function getSignalTresorerie(?TresorerieEtat $tresorerie): string
    {
        if (!$tresorerie || $tresorerie->getSoldeAvantFin() === null)
            return 'secondary';

        $solde = (float) $tresorerie->getSoldeAvantFin();
        $seuilRouge = $this->getParam('SEUIL_TRESORERIE_ROUGE', -100);
        $seuilJaune = $this->getParam('SEUIL_TRESORERIE_JAUNE', 0);

        if ($solde < $seuilRouge)
            return 'red';
        if ($solde < $seuilJaune)
            return 'yellow';

        return 'green';
    }

    public function getSignalPaie(?PaieEtat $paie): string
    {
        $reste = $this->getPctRestePayie($paie);
        if ($reste === null)
            return 'secondary';

        $seuilRouge = $this->getParam('SEUIL_PAIE_ROUGE', 50);
        $seuilOrange = $this->getParam('SEUIL_PAIE_ORANGE', 20);

        if ($reste > $seuilRouge)
            return 'red';
        if ($reste > $seuilOrange)
            return 'orange';

        return 'yellow';
    }

    // ─────────────────────────────────────────────────────────────────────
    // 5. SCÉNARIO DE PILOTAGE RECOMMANDÉ (1 / 2 / 3)
    // ─────────────────────────────────────────────────────────────────────

    public function getScenarioPilotage(
        ?MarcheChanges $marche,
        ?ReservesFinancieres $reserves,
        ?TresorerieEtat $tresorerie,
        ?PaieEtat $paie
    ): array {
        $ecartPct = $this->getEcartPct($marche);
        $ecartMax = $this->getEcartMaxPct($marche);
        $avLibres = $reserves && $reserves->getAvoirsLibresCdf() !== null ? (float) $reserves->getAvoirsLibresCdf() : null;
        $soldeAvFin = $tresorerie && $tresorerie->getSoldeAvantFin() !== null ? (float) $tresorerie->getSoldeAvantFin() : null;
        $pctReste = $this->getPctRestePayie($paie);

        $seuilChangeRouge = $this->getParam('SEUIL_CHANGE_ROUGE', 5.0);
        $seuilMaxChangeRouge = $this->getParam('SEUIL_MAX_CHANGE_ROUGE', 6.0);

        $isRouge = ($ecartPct !== null && $ecartPct >= $seuilChangeRouge)
            || ($ecartMax !== null && $ecartMax >= $seuilMaxChangeRouge);

        if ($isRouge) {
            return [
                'scenario' => 3,
                'label' => 'Action forte coordonnée',
                'color' => 'red',
                'justification' => sprintf(
                    'Écart change %.1f%% (seuil %.1f%%) — action immédiate requise.',
                    $ecartPct ?? 0,
                    $seuilChangeRouge
                ),
            ];
        }

        $seuilChangeOrange = $this->getParam('SEUIL_CHANGE_ORANGE', 3.0);
        $seuilLiqOrange = $this->getParam('SEUIL_LIQUIDITE_ORANGE', 1200); // 1200 en orange sur l'ancien code (?)
        $seuilTresOrange = $this->getParam('SEUIL_TRESORERIE_ORANGE', -100);
        $seuilPaieOrange = $this->getParam('SEUIL_PAIE_SCENARIO_ORANGE', 30);

        $isOrange = ($ecartPct !== null && $ecartPct >= $seuilChangeOrange)
            || ($avLibres !== null && $avLibres >= $seuilLiqOrange)
            || ($soldeAvFin !== null && $soldeAvFin < $seuilTresOrange)
            || ($pctReste !== null && $pctReste >= $seuilPaieOrange);

        if ($isOrange) {
            $raisons = [];
            if ($ecartPct !== null && $ecartPct >= $seuilChangeOrange)
                $raisons[] = sprintf('Écart change %.1f%%', $ecartPct);
            if ($avLibres !== null && $avLibres >= $seuilLiqOrange)
                $raisons[] = sprintf('Avoirs libres %.0f Mds CDF', $avLibres);
            if ($soldeAvFin !== null && $soldeAvFin < $seuilTresOrange)
                $raisons[] = sprintf('Solde trésorerie %.0f Mds', $soldeAvFin);
            if ($pctReste !== null && $pctReste >= $seuilPaieOrange)
                $raisons[] = sprintf('Paie restante %.0f%%', $pctReste);

            return [
                'scenario' => 2,
                'label' => 'Ajustement préventif',
                'color' => 'orange',
                'justification' => implode(' · ', $raisons),
            ];
        }

        return [
            'scenario' => 1,
            'label' => 'Status quo discipliné',
            'color' => 'green',
            'justification' => 'Indicateurs dans les normes — surveillance maintenue.',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // 6. PHRASE CABINET AUTOMATIQUE
    // ─────────────────────────────────────────────────────────────────────

    public function getPhraseCabinet(
        ?MarcheChanges $marche,
        ?ReservesFinancieres $reserves,
        ?TresorerieEtat $tresorerie,
        ?PaieEtat $paie,
        array $scenario
    ): string {
        $ecartPct = $this->getEcartPct($marche);
        $avLibres = $reserves && $reserves->getAvoirsLibresCdf() !== null ? (float) $reserves->getAvoirsLibresCdf() : null;
        $soldeAvFin = $tresorerie && $tresorerie->getSoldeAvantFin() !== null ? (float) $tresorerie->getSoldeAvantFin() : null;

        $change = $ecartPct !== null
            ? sprintf("un écart change de %.1f%%", $ecartPct)
            : "des données de change partielles";

        $liquidite = $avLibres !== null
            ? sprintf("des avoirs libres à %.0f Mds CDF", $avLibres)
            : "une liquidité non disponible";

        $trésorerie = $soldeAvFin !== null
            ? ($soldeAvFin >= 0
                ? "une trésorerie équilibrée"
                : sprintf("un déficit de trésorerie de %.0f Mds CDF", abs($soldeAvFin)))
            : "une trésorerie non disponible";

        $scenarioLabel = match ($scenario['scenario']) {
            3 => "exige une action monétaire forte et coordonnée",
            2 => "recommande un ajustement préventif de la stérilisation et une coordination étroite avec le Trésor",
            default => "plaide pour le maintien de la discipline monétaire actuelle",
        };

        return "Les indicateurs du jour font état de {$change}, {$liquidite} et {$trésorerie}. "
            . "Au regard de ces éléments, la situation {$scenarioLabel}.";
    }

    public function colorToBootstrap(string $color): string
    {
        return match ($color) {
            'red' => 'danger',
            'orange' => 'warning',
            'yellow' => 'info',
            'green' => 'success',
            default => 'secondary',
        };
    }

    public function colorToEmoji(string $color): string
    {
        return match ($color) {
            'red' => '🔴',
            'orange' => '🟠',
            'yellow' => '🟡',
            'green' => '🟢',
            default => '⚪',
        };
    }
}
