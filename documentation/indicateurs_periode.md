# Documentation : Page "Indicateurs de Période"

La page **Indicateurs de Période** a pour objectif de consolider les données macroéconomiques de la Banque Centrale du Congo (BCC) sur un intervalle de dates défini par l'utilisateur (par défaut, les 90 derniers jours). 
Elle affiche les moyennes et extremums de la période, génère des signaux d'alerte automatiques et propose une recommandation d'orientation pour le Taux Directeur.

---

## 1. Méthode de calcul des Agrégats

Tous les agrégats sont calculés sur **les données journalières existantes** dont la `date_situation` est comprise entre la `Date de Début` et la `Date de Fin` sélectionnées.

### 🏦 Pilier 1 : Marché des Changes
Les données proviennent de la table `marche_changes`.
- **Écart indicatif/parallèle (%) :**
  Pour chaque jour, l'écart est calculé ainsi :
  `Écart = (( (Parallèle Achat + Parallèle Vente) / 2 ) - Indicatif) / Indicatif * 100`
- **Écart Moyen (`ecartMoy`) :** La moyenne de cet écart sur tous les jours de la période.
- **Écart Maximum (`ecartMax`) :** La valeur la plus haute atteinte par l'écart sur la période.

### 🌍 Pilier 2 : Position Extérieure (Réserves)
Les données proviennent de la table `reserves_financieres`.
- **Réserves Internationales Moyennes (`reservesIntMoy`) :** Moyenne des `reserves_internationales_usd` de la période. Utilisé pour vérifier si l'ancre externe est solide.

### 💧 Pilier 3 : Liquidité et Stérilisation
Les données proviennent des tables `reserves_financieres` et `encours_bcc`.
- **Avoirs libres moyens et max (`avLibresMoy` / `avLibresMax`) :** Moyenne et pic maximal des `avoirs_libres_cdf` des banques commerciales, permettant de mesurer la surliquidité sur le marché.
- **Stérilisation Moyenne (`sterilisationMoy`) :** Moyenne de la somme `encours_ot_bcc` (Obligations du Trésor) + `encours_b_bcc` (Bons BCC). Indique les efforts d'épongeage de la liquidité.

### 🏛️ Pilier 4 : Budget et Trésorerie
Les données proviennent de la table `finances_publiques`.
- **Solde Moyen (`soldeMoy`) :** Moyenne des soldes journaliers (`recettes_totales - depenses_totales`) du Trésor pour observer la tendance du compte de l'État.
- **Recettes / Dépenses Cumulées :** Somme glissante de la période pour donner un aperçu du volume.

---

## 2. Système d'Alertes et Signaux

La page attribue pour 3 piliers un **code couleur (Signal)** selon des seuils stricts définis dans le contrôleur (`IndicateursPeriodeController`).

### Signal Change (Marché des changes)
Mesure la pression de la dépréciation via l'écart sur le marché parallèle.
- **🔴 Critique (Rouge)** : Écart moyen > 5.0% OU Écart max > 6.0%
- **🟠 Alerte (Orange)** : Écart moyen > 3.0% OU Écart max > 3.0%
- **🟡 Vigilance (Jaune)** : Écart moyen > 2.0%
- **🟢 Normal (Vert)** : Écart moyen ≤ 2.0% (L'écart est considéré aligné).

### Signal Liquidité
Mesure les excédents monétaires (Avoirs libres) risquant d'alimenter la spéculation sur le taux de change.
- **🔴 Critique (Rouge)** : Pic des avoirs libres (Max) > 1 200 Milliards CDF.
- **🟠 Alerte (Orange)** : Pic des avoirs libres (Max) > 800 Milliards CDF.
- **🟢 Normal (Vert)** : Avoirs libres sous la barre des 800 Mds CDF.

### Signal Trésorerie (Budget)
Mesure le risque de monétisation du déficit.
- **🔴 Critique (Rouge)** : Solde moyen < -100 Milliards CDF.
- **🟡 Vigilance (Jaune)** : Solde moyen < 0 (Déficit mineur).
- **🟢 Normal (Vert)** : Solde moyen ≥ 0.

**Signal Global :** L'application retient le *pire* signal parmi le Change, la Liquidité et la Trésorerie pour alerter la Haute Direction. Un seul pilier au Rouge met l'évaluation globale au Rouge.

---

## 3. L'Algorithme de Recommandation du Taux Directeur

La recommandation pour le Comité de Politique Monétaire utilise un système de "Points de Pression". Plus ce score est élevé, moins il est recommandé de baisser le taux directeur.

*Score initial de Pression = 0.*

**Calcul des pressions :**
- **Sur le Change :** 
  - Si Écart Moyen > 5% : **+2 points**
  - Si Écart Moyen > 3% : **+1 point**
  - Si Écart Moyen ≤ 2% : **-1 point** (Condition propice au relâchement)
- **Sur la Liquidité :**
  - Si Avoirs Libres Max > 1200 Mds : **+1 point**
  - Si Avoirs Libres Max < 500 Mds : **-1 point** (Liquidité tendue)
- **Sur la Trésorerie :**
  - Si Solde Moyen < -100 Mds : **+1 point**

**Décision Finale selon les points de pression :**
- 🔴 **Points ≥ 2 : MAINTENIR le taux.** (L'inflation/dépréciation est trop forte, il est indispensable de garder le taux élevé pour ancrer les anticipations de l'économie).
- 🟠 **Points = 1 : VIGILANCE.** (Pas de hausse nécessaire, mais toute baisse du taux actuel serait jugée prématurée).
- 🟢 **Points ≤ -1 : BAISSER le taux.** (Les indicateurs sont au vert, l'inflation/le change est maîtrisé(e) et la liquidité n'est pas explosive, on peut envisager un assouplissement de la politique).
- ⚪ **Points = 0 : STATU QUO.** (Les indicateurs sont globalement plats ou contradictoires mais mineurs, on maintient sous surveillance).

### Récupération du Taux Actuel
La logique interroge la table `taux_directeur` en base de données. Elle recherche le taux dont la `dateApplication` est la plus proche (inférieure ou égale) de la **Date de Fin** de la période consultée. Cela permet de toujours analyser les agrégats à la lumière de la politique monétaire qui était en vigueur au même moment.
