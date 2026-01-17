# Documentation Fonctionnelle : Règles de Gestion et ITM

Ce document détaille les règles de gestion, les indicateurs surveillés et la méthodologie de calcul de l'Indice de Tension du Marché (ITM) implémentés dans BCC-Flex.

## 1. Indicateurs Surveillés

Le système surveille un ensemble d'indicateurs stratégiques classés par importance.

### Indicateurs de 1er Rang (Composantes de l'ITM)

Ces indicateurs influencent directement le calcul du score de tension du marché.

| Indicateur | Code | Unité | Source de Données | Description | Sens de Variation Critique |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Écart de Change** | `ECART_CHANGE` | % ou CDF | `KPIJournalier.ecart_indic_parallele` | Différence entre le taux parallèle vendeur et le taux indicatif BCC. | 📈 Hausse (Défavorable) |
| **Avoirs Libres** | `AVOIRS_LIBRES` | Mds CDF | `ReservesFinancieres.avoirs_libres_cdf` | Liquidités en Francs Congolais disponibles à la BCC. | 📉 Baisse (Défavorable) |
| **Réserves de Change** | `RESERVES` | Mio USD | `ReservesFinancieres.reserves_internationales_usd` | Stock de devises étrangères détenu par la BCC. | 📉 Baisse (Défavorable) |
| **Ventes USD BCC** | `VENTES_BCC` | Mio USD | `VolumeUSD` (Somme des Ventes) | Volume d'intervention de la BCC (vente de devises) sur le marché. | 📈 Hausse (Défavorable) |
| **Position de Change** | `POSITION_CHANGE` | Mio USD | `PositionChange` (Calculé) | Position nette en devises de la BCC. | 📉 Baisse (Défavorable) |

---

## 2. Règles de Gestion et Seuils

Pour chaque indicateur, deux seuils critiques sont définis. Ils déterminent le niveau d'alerte et le score partiel de l'indicateur.

### Niveaux d'Alerte

1.  **Zone Normale (Vert)** : La valeur est sous contrôle.
    *   *Action* : Aucune.
2.  **Zone de Vigilance (Orange)** : La valeur a franchi le premier seuil d'attention.
    *   *Action* : Surveillance accrue, analyse des causes.
3.  **Zone d'Intervention (Rouge)** : La valeur a franchi le seuil critique.
    *   *Action* : Intervention recommandée sur le marché, mesures correctives.

### Configuration des Seuils (Exemple)

> **Note** : Les seuils sont configurables dynamiquement dans la table `regle_intervention`.

| Indicateur | Seuil Vigilance (S1) | Seuil Intervention (S2) |
| :--- | :--- | :--- |
| **Écart de Change** | > 1.5 % | > 2.5 % |
| **Avoirs Libres** | < 100 Mds CDF | < 50 Mds CDF |
| **Réserves de Change** | < 1000 Mio USD | < 800 Mio USD |

---

## 3. Calcul de l'Indice de Tension du Marché (ITM)

L'ITM est un indicateur composite (score de 0 à 100) qui synthétise la santé globale du marché.

### Formule de Calcul

L'ITM est la moyenne pondérée des scores de chaque indicateur.

$$
ITM = \frac{\sum (Score_i \times Poids_i)}{\sum Poids_i}
$$

### Pondération

Les poids déterminent l'importance relative de chaque indicateur dans le score final.

| Indicateur | Poids |
| :--- | :---: |
| Écart de Change | **30 %** |
| Avoirs Libres | **20 %** |
| Réserves de Change | **20 %** |
| Ventes USD BCC | **15 %** |
| Position de Change | **15 %** |
| **TOTAL** | **100 %** |

### Algorithme de Calcul du Score Individuel ($Score_i$)

Le score individuel d'un indicateur dépend de sa position par rapport aux seuils (Vigilance $S1$ et Intervention $S2$).

#### Cas A : La hausse est défavorable (ex: Écart de change)
*   **Si Valeur $\le S1$** : $Score = 0$ (Normal)
*   **Si $S1 <$ Valeur $\le S2$** : $Score = 50$ (Vigilance)
*   **Si Valeur $> S2$** : $Score = 100$ (Intervention)

#### Cas B : La baisse est défavorable (ex: Réserves)
*   **Si Valeur $\ge S1$** : $Score = 0$ (Normal)
*   **Si $S2 \le$ Valeur $< S1$** : $Score = 50$ (Vigilance)
*   **Si Valeur $< S2$** : $Score = 100$ (Intervention)

### Interprétation du Score Global ITM

Le résultat final détermine l'état du marché :

| Score ITM | Niveau | Description |
| :--- | :--- | :--- |
| **0 – 30** | 🟢 **NORMAL** | Situation stable. Les fondamentaux sont solides. |
| **30 – 60** | 🟠 **VIGILANCE** | Tensions observées. Plusieurs indicateurs sont en alerte. Préparation recommandée. |
| **> 60** | 🔴 **INTERVENTION** | Situation critique. Intervention immédiate recommandée pour stabiliser le marché. |

---

## 4. Matrice de Décision Automatique

Le système génère automatiquement une recommandation basée sur l'ITM et les alertes individuelles.

**Exemple de Note Technique Générée :**

> *"Au regard de l'Indice de Tension du Marché (ITM) affichant un score de **72/100** (Zone Rouge), et considérant le dépassement simultané des seuils d'intervention pour les **Avoirs Libres** (45 Mds CDF) et l'**Écart de Change** (3.1%), une intervention sur le marché des changes est recommandée ce jour."*
