# Documentation Fonctionnelle : Module d'Importation

Ce document décrit le fonctionnement du module d'importation des données de conjoncture économique dans BCC-Flex.

## 1. Vue d'Ensemble

Le module d'importation permet aux administrateurs de charger en masse les données journalières via des fichiers Excel (`.xlsx`, `.xls`) ou CSV. Le système traite ces fichiers pour mettre à jour automatiquement les indicateurs financiers et recalculer les alertes.

### Fonctionnalités Clés
*   **Support Multi-formats** : Excel et CSV.
*   **Détection Automatique** : Identification intelligente des colonnes et du type de données.
*   **Gestion des Doublons** : Mise à jour des enregistrements existants ou création de nouveaux.
*   **Calcul Automatique** : Déclenchement automatique du recalcul des alertes après import.

---

## 2. Formats de Fichiers Acceptés

### Structure Générale
Le fichier doit contenir une ligne d'en-tête définissant les noms des colonnes. L'ordre des colonnes n'a pas d'importance grâce au mapping automatique.

### Colonnes Reconnues par Catégorie

#### Marché des Changes
| En-tête (Colonne) | Description | Format Attendu |
| :--- | :--- | :--- |
| `cours_indicatif` | Taux indicatif BCC | Numérique (ex: `2850.50`) |
| `parallele_achat` | Taux parallèle (Achat) | Numérique |
| `parallele_vente` | Taux parallèle (Vente) | Numérique |
| `ecart` | Écart (Optionnel, calculé si absent) | Numérique |

#### Réserves Financières
| En-tête (Colonne) | Description | Format Attendu |
| :--- | :--- | :--- |
| `reserves_internationales_usd` | Réserves en USD | Numérique |
| `avoirs_externes_usd` | Avoirs externes | Numérique |
| `avoirs_libres_cdf` | Avoirs libres en CDF | Numérique |

#### Finances Publiques
| En-tête (Colonne) | Description | Format Attendu |
| :--- | :--- | :--- |
| `recettes_totales` | Total des recettes | Numérique |
| `depenses_totales` | Total des dépenses | Numérique |
| `solde` | Solde budgétaire | Numérique |

> **Note sur le format numérique** : Le système gère automatiquement les espaces (séparateurs de milliers) et remplace les virgules par des points décimaux.

---

## 3. Processus d'Importation (Guide Utilisateur)

1.  **Accès** : Naviguer vers la section **Administration > Import**.
2.  **Sélection** :
    *   Choisir la **Date** de la conjoncture (correspondant aux données du fichier).
    *   Sélectionner le **Fichier** sur votre poste.
3.  **Validation** : Cliquer sur "Importer".
4.  **Résultat** :
    *   Le système affiche le nombre de lignes traitées.
    *   En cas d'erreur sur une ligne, un rapport détaillé est affiché.

---

## 4. Logique Technique (Pour les Développeurs)

Le contrôleur principal est `ImportController` (`src/Controller/ImportController.php`).

### Flux de Traitement
1.  **Upload** : Réception du fichier et de la date via formulaire `POST`.
2.  **Initialisation Conjoncture** :
    *   Recherche d'une `ConjonctureJour` existante pour la date donnée.
    *   Si existante : mode **Mise à jour**.
    *   Si inexistante : création d'une **Nouvelle** entité.
3.  **Parsing** :
    *   Utilisation de `PhpSpreadsheet` pour Excel.
    *   Parsing natif pour CSV.
    *   Normalisation des en-têtes (trim, minuscules).
4.  **Mapping Intelligent** (`processImportData`) :
    *   Le système tente de détecter le type de données de chaque ligne via une colonne `type` ou `categorie` si présente.
    *   **Auto-détection** (`autoDetectAndImport`) : Analyse les clés présentes dans la ligne pour déduire l'entité cible (ex: présence de `cours_indicatif` -> `MarcheChanges`).
5.  **Persistance** :
    *   Les entités filles (`MarcheChanges`, `ReservesFinancieres`, etc.) sont créées et liées à la `ConjonctureJour`.
    *   Utilisation de transactions implicites via `EntityManager`.
6.  **Post-Traitement** :
    *   Appel à `AlerteService::calculateAlerts()` pour mettre à jour le statut des indicateurs basés sur les nouvelles données.

### Gestion des Erreurs
Le processus est tolérant aux pannes partielles :
*   Les erreurs de parsing sur une ligne spécifique sont capturées et rapportées (via `try/catch` dans la boucle).
*   L'import global n'est pas interrompu par une ligne défectueuse isolée.
