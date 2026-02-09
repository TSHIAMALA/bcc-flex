# 📊 IndiceTensionService - Documentation

## Objectif

Ce service calcule l'**ITM (Indice de Tension du Marché)**, un score composite entre **0 et 100** qui mesure le niveau de tension sur le marché des changes. Il permet de déterminer si une intervention de la banque centrale est nécessaire.

---

## 🎯 Classifications et Seuils

| Score ITM   | Classification   | Label                      | Couleur              |
|-------------|------------------|----------------------------|----------------------|
| **0 - 29**  | `NORMAL`         | Situation normale          | 🟢 Vert (`#10b981`)  |
| **30 - 59** | `VIGILANCE`      | Vigilance requise          | 🟡 Orange (`#f59e0b`)|
| **60 - 100**| `INTERVENTION`   | Intervention recommandée   | 🔴 Rouge (`#ef4444`) |

---

## 🔧 Dépendances

```php
public function __construct(
    private AlerteService $alerteService,               // Pour récupérer les valeurs des indicateurs
    private IndicateurRepository $indicateurRepository, // Accès aux indicateurs configurés
    private RegleInterventionRepository $regleRepository // Règles d'intervention
)
```

---

## 📐 Méthode principale : `calculateITM()`

```php
public function calculateITM(?ConjonctureJour $conjoncture): array
```

**Entrée** : Un objet `ConjonctureJour` contenant les données du jour.

**Sortie** :

| Clé              | Description                                    |
|------------------|------------------------------------------------|
| `score`          | Score ITM (0-100) arrondi à 1 décimale         |
| `classification` | `NORMAL`, `VIGILANCE` ou `INTERVENTION`        |
| `label`          | Libellé humain (ex: "Vigilance requise")       |
| `details`        | Tableau des scores par indicateur              |

---

## 📈 Calcul du score individuel : `calculateIndicatorScore()`

Le score d'un indicateur (0-100) dépend de son **sens** (`hausse` ou `baisse`) :

### Sens "hausse" (ex: taux de change)

*Plus la valeur est haute, plus la tension est élevée*

| Plage de valeur                          | Score   | Zone        |
|------------------------------------------|---------|-------------|
| Valeur ≤ Seuil Vigilance                 | 0-30    | Normal      |
| Vigilance < Valeur ≤ Intervention        | 30-60   | Vigilance   |
| Valeur > Intervention                    | 60-100  | Intervention|

### Sens "baisse" (ex: réserves de change)

*Plus la valeur est basse, plus la tension est élevée*

| Plage de valeur                          | Score   | Zone        |
|------------------------------------------|---------|-------------|
| Valeur ≥ Seuil Vigilance                 | 0-30    | Normal      |
| Intervention ≤ Valeur < Vigilance        | 30-60   | Vigilance   |
| Valeur < Intervention                    | 60-100  | Intervention|

---

## 🧮 Formule de calcul ITM

```
ITM = Σ (score_indicateur × poids) / Σ poids
```

Le score final est une **moyenne pondérée** de tous les indicateurs, chaque indicateur ayant un poids défini dans ses règles (`RegleIntervention::getPoids()`).

---

## 🎨 Méthodes utilitaires

| Méthode                                  | Retour   | Usage                              |
|------------------------------------------|----------|------------------------------------|
| `getITMClassification($score)`           | `string` | Classification selon le score     |
| `getClassificationLabel($classification)`| `string` | Libellé français                   |
| `getClassificationClass($classification)`| `string` | Classe CSS (`success`, `warning`, `danger`) |
| `getClassificationColor($classification)`| `string` | Code couleur hexadécimal           |

---

## 📌 Exemple d'utilisation

```php
// Dans un contrôleur
$conjoncture = $conjonctureRepository->findOneBy([], ['dateValeur' => 'DESC']);
$itm = $indiceTensionService->calculateITM($conjoncture);

// Résultat typique
[
    'score' => 45.2,
    'classification' => 'VIGILANCE',
    'label' => 'Vigilance requise',
    'details' => [
        [
            'indicateur' => 'Taux USD',
            'code' => 'TAUX_USD',
            'valeur' => 2850.5,
            'score' => 52.3,
            'poids' => 3,
            'statut' => 'warning'
        ],
        // ... autres indicateurs
    ]
]
```

---

## 🔗 Fichiers liés

- `src/Service/IndiceTensionService.php` - Implémentation du service
- `src/Service/AlerteService.php` - Récupération des valeurs d'indicateurs
- `src/Entity/ConjonctureJour.php` - Données conjoncturelles journalières
- `src/Entity/RegleIntervention.php` - Règles et seuils d'intervention
- `src/Entity/Indicateur.php` - Définition des indicateurs
