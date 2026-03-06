# IndicateursCalculService

- **Fichier :** [src/Service/IndicateursCalculService.php](src/Service/IndicateursCalculService.php)
- **But :** Calculer et agréger les indicateurs dérivés pour la surveillance macro‑financière (change, liquidité, stérilisation, paie, trésorerie). Fournit des signaux couleur, un scénario de pilotage et une phrase synthétique pour la communication "cabinet".

## Résumé

Service central qui lit des seuils dynamiques depuis `ParametreGlobalRepository` et calcule :

- Écarts de change (absolu, %), spread parallèle, écart max.
- Totaux d'instruments de stérilisation et ratio de stérilisation.
- Taux d'exécution de paie et pourcentage restant.
- Signaux couleur (`green|yellow|orange|red|secondary`) pour change, liquidité, trésorerie, paie.
- Scénario de pilotage (1/2/3) avec `label`, `color` et `justification`.
- Phrase cabinet automatique résumant la situation.
- Helpers de conversion couleur → Bootstrap / emoji.

## Dépendances

- `ParametreGlobalRepository` (injection via constructeur) — lecture des paramètres/seuils.
- Entités d'entrée : `MarcheChanges`, `EncoursBcc`, `ReservesFinancieres`, `TresorerieEtat`, `PaieEtat`.

## Chargement des paramètres

Le constructeur charge tous les paramètres `ParametreGlobal` en mémoire via `loadParams()` et les expose par `getParam(string $code, float $default): float`.

## Paramètres / codes de seuils utilisés

- `SEUIL_CHANGE_ROUGE` (défaut 5.0)
- `SEUIL_MAX_CHANGE_ROUGE` (défaut 6.0)
- `SEUIL_CHANGE_ORANGE` (défaut 3.0)
- `SEUIL_MAX_CHANGE_ORANGE` (défaut 3.0)
- `SEUIL_CHANGE_JAUNE` (défaut 2.0)
- `SEUIL_LIQUIDITE_ROUGE` (défaut 1200)
- `SEUIL_LIQUIDITE_ORANGE` (défaut 800)
- `SEUIL_TRESORERIE_ROUGE` (défaut -100)
- `SEUIL_TRESORERIE_JAUNE` (défaut 0)
- `SEUIL_PAIE_ROUGE` (défaut 50)
- `SEUIL_PAIE_ORANGE` (défaut 20)
- `SEUIL_PAIE_SCENARIO_ORANGE` (défaut 30)
- `SEUIL_TRESORERIE_ORANGE` (défaut -100)

> Remarque : des valeurs par défaut sont fournies dans le code si le paramètre est absent en base.

## Méthodes publiques principales

- `getMidParallele(?MarcheChanges $marche): ?float`
  - Moyenne (achat+vente)/2 du marché parallèle. Null si données manquantes.

- `getEcartAbsolu(?MarcheChanges $marche): ?float`
  - Différence entre mid parallèle et cours indicatif.

- `getEcartPct(?MarcheChanges $marche): ?float`
  - Pourcentage d'écart (écart absolu / cours indicatif * 100). Null si indicatif absent ou = 0.

- `getEcartMaxPct(?MarcheChanges $marche): ?float`
  - ((parallèle vente − indicatif) / indicatif) * 100.

- `getSpreadParallelePct(?MarcheChanges $marche): ?float`
  - Spread relatif (vente − achat) / mid * 100.

- `getTotalSterilisation(?EncoursBcc $encours): ?float`
  - Somme des encours OT-BCC et B-BCC.

- `getRatioSterilisation(?EncoursBcc $encours, ?ReservesFinancieres $reserves): ?float`
  - totalSterilisation / avoirsLibresCdf. Null si données manquantes ou division par zéro.

- `getTauxExecutionPaie(?PaieEtat $paie): ?float`
  - (montantPaye / montantTotal) * 100.

- `getPctRestePayie(?PaieEtat $paie): ?float`
  - 100 − taux execution. Retourne null si le taux est null.

- `getSignalChange(?MarcheChanges $marche): string`
  - `secondary|green|yellow|orange|red` selon `ecartPct` et `ecartMaxPct` comparés aux seuils.

- `getSignalLiquidite(?ReservesFinancieres $reserves): string`
  - `secondary|green|orange|red` selon `avoirsLibresCdf`.

- `getSignalTresorerie(?TresorerieEtat $tresorerie): string`
  - `secondary|green|yellow|red` selon `soldeAvantFin`.

- `getSignalPaie(?PaieEtat $paie): string`
  - `secondary|yellow|orange|red` selon pourcentage restant de paie.

- `getScenarioPilotage(...): array`
  - Retourne `['scenario'=>1|2|3, 'label'=>string, 'color'=>string, 'justification'=>string]`.
  - Logique :
    - Scenario 3 si change rouge (écartPct >= seuilChangeRouge ou ecartMax >= seuilMax).
    - Scenario 2 si conditions orange (écartPct >= seuilChangeOrange OR avoirsLibres >= seuilLiqOrange OR soldeAvFin < seuilTresOrange OR pctReste >= seuilPaieOrange).
    - Sinon scenario 1.

- `getPhraseCabinet(...): string`
  - Génère une phrase synthétique combinant change, liquidité, trésorerie et le scénario.

- `colorToBootstrap(string $color): string` et `colorToEmoji(string $color): string`
  - Helpers de mapping de couleur.

## Bonnes pratiques & recommandations

- Ajouter des tests unitaires pour : calculs d'écarts, gestion des nulls, logique de seuils et génération de phrase.
- Rendre le chargement des paramètres rafraîissable si les seuils sont modifiés sans redémarrage.
- Documenter (ou migrer) la liste des `ParametreGlobal` attendus pour faciliter la configuration.

## Exemples d'utilisation (pseudo‑code)

```php
$service = $container->get(App\Service\IndicateursCalculService::class);
$signalChange = $service->getSignalChange($marche);
$scenario = $service->getScenarioPilotage($marche, $reserves, $tresorerie, $paie);
$phrase = $service->getPhraseCabinet($marche, $reserves, $tresorerie, $paie, $scenario);
```

## Notes de maintenance

- Les méthodes renvoient `null` quand un calcul est impossible (données manquantes ou division par zéro) — les consommateurs doivent en tenir compte.
- Vérifier la cohérence métier des seuils partagés entre signaux et scénarios (`SEUIL_LIQUIDITE_ORANGE` est utilisé à plusieurs endroits).

---

Fichier généré automatiquement depuis le code source le 2026-03-05.
