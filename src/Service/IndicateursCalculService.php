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
    // 2. MARCHÉ MONÉTAIRE
    // ─────────────────────────────────────────────────────────────────────

    public function getTotalEncoursBons(?EncoursBcc $encours): ?float
    {
        if (!$encours)
            return null;
        $ot = $encours->getEncoursOtBcc() !== null ? (float) $encours->getEncoursOtBcc() : 0.0;
        $b = $encours->getEncoursBBcc() !== null ? (float) $encours->getEncoursBBcc() : 0.0;
        return $ot + $b;
    }

    public function getRatioEncoursBons(?EncoursBcc $encours, ?ReservesFinancieres $reserves): ?float
    {
        $total = $this->getTotalEncoursBons($encours);
        if ($total === null || !$reserves || $reserves->getAvoirsLibresCdf() === null)
            return null;
        $avoirsLibres = (float) $reserves->getAvoirsLibresCdf();
        if ($avoirsLibres == 0)
            return null;
        return $total / $avoirsLibres;
    }

    public function getSignalMarcheMonetaire(?EncoursBcc $encours, ?ReservesFinancieres $reserves): string
    {
        if (!$reserves || $reserves->getAvoirsLibresCdf() === null) {
            return 'secondary';
        }
        $alo = (float) $reserves->getAvoirsLibresCdf();
        if ($alo > 800) {
            return 'red';
        } elseif ($alo > 400) {
            return 'orange';
        } elseif ($alo < 100) {
            return 'yellow';
        }
        return 'green';
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
                'label' => 'Resserrement coordonné — Intervention urgente',
                'color' => 'red',
                'justification' => sprintf(
                    "L'écart de change (%.1f%%) franchit le seuil critique de %.1f%% — une intervention monétaire coordonnée est requise sans délai pour enrayer l'érosion de la crédibilité du taux indicatif.",
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
                $raisons[] = sprintf("Écart indicatif/parallèle de %.1f%% en zone orange — la marge de tolérance est épuisée", $ecartPct);
            if ($avLibres !== null && $avLibres >= $seuilLiqOrange)
                $raisons[] = sprintf("Surliquidité bancaire à %.0f Mds CDF — constitue un carburant latent pour la demande de devises", $avLibres);
            if ($soldeAvFin !== null && $soldeAvFin < $seuilTresOrange)
                $raisons[] = sprintf("Solde de trésorerie de l'État à %.0f Mds CDF — risque de recours monétaire", $soldeAvFin);
            if ($pctReste !== null && $pctReste >= $seuilPaieOrange)
                $raisons[] = sprintf("Arriérés de paie à %.0f%% — risque d'injection monétaire non stérilisée", $pctReste);

            return [
                'scenario' => 2,
                'label' => 'Vigilance active — Ajustement préventif',
                'color' => 'orange',
                'justification' => implode('. ', $raisons) . '.',
            ];
        }

        return [
            'scenario' => 1,
            'label' => 'Neutralité restrictive — Discipline maintenue',
            'color' => 'green',
            'justification' => "L'ensemble des piliers se situent dans leurs zones de référence. La posture de neutralité restrictive peut être maintenue, sous surveillance continue des avoirs libres et de l'écart de change.",
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
        $resIntl = $reserves && $reserves->getReservesInternationalesUsd() !== null ? (float) $reserves->getReservesInternationalesUsd() : null;
        $soldeAvFin = $tresorerie && $tresorerie->getSoldeAvantFin() !== null ? (float) $tresorerie->getSoldeAvantFin() : null;

        // Acte 1 — Constat factuel
        $changePhrase = $ecartPct !== null
            ? sprintf("un écart indicatif/parallèle de %.1f%%", $ecartPct)
            : "des données de change partielles";

        $liquPhrase = $avLibres !== null
            ? ($avLibres > 1200
                ? sprintf("une surliquidité bancaire structurelle à %.0f Mds CDF", $avLibres)
                : ($avLibres < 500
                    ? sprintf("une liquidité bancaire contrainte à %.0f Mds CDF", $avLibres)
                    : sprintf("une liquidité bancaire maîtrisée à %.0f Mds CDF", $avLibres)))
            : "une position de liquidité non disponible";

        $tresoPhrase = $soldeAvFin !== null
            ? ($soldeAvFin >= 100
                ? sprintf("un solde budgétaire excédentaire de %.0f Mds CDF", $soldeAvFin)
                : ($soldeAvFin >= 0
                    ? "un solde de trésorerie marginalement positif"
                    : sprintf("un solde de trésorerie déficitaire de %.0f Mds CDF", abs($soldeAvFin))))
            : "une position budgétaire non disponible";

        // Acte 2 — Diagnostic de risque
        $risquePhrase = match ($scenario['scenario']) {
            3 => "traduit un désalignement structurel exigeant une intervention monétaire coordonnée sans délai",
            2 => "révèle des fragilités persistantes imposant une vigilance renforcée et un ajustement préventif de la stérilisation",
            default => "reste globalement dans les normes de référence, sous réserve d'une surveillance continue",
        };

        // Acte 3 — Orientation opérationnelle
        $orientationPhrase = match ($scenario['scenario']) {
            3 => "Une action coordonnée entre la BCC et le Trésor est requise pour enrayer la dynamique de dépréciation.",
            2 => "La priorité est à l'absorption de liquidité et au calage du calendrier de paiements publics pour éviter tout choc sur le marché des changes.",
            default => "La discipline monétaire en vigueur peut être maintenue. Toute inflexion accommodante devra être conditionnée à une réduction durable de l'écart de change.",
        };

        return "La situation du jour enregistre {$changePhrase}, {$liquPhrase} et {$tresoPhrase}. "
            . "L'ensemble de ces indicateurs {$risquePhrase}. "
            . $orientationPhrase;
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
