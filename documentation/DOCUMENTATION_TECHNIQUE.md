# Documentation Technique BCC-Flex

## Table des Matières

1. [Vue d'Ensemble](#vue-densemble)
2. [Architecture de l'Application](#architecture-de-lapplication)
3. [Formules de Calcul des Statistiques](#formules-de-calcul-des-statistiques)
4. [Guide d'Utilisation](#guide-dutilisation)
5. [Guide de Développement](#guide-de-développement)
6. [Améliorations Responsive Mobile](#améliorations-responsive-mobile)

---

## Vue d'Ensemble

**BCC-Flex** est une application web de tableau de bord de conjoncture économique développée pour la Banque Centrale du Congo (BCC). Elle permet de visualiser et d'analyser en temps réel les principaux indicateurs économiques et financiers du pays.

### Technologies Utilisées

- **Backend**: Symfony 7.x (PHP 8.2+)
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Graphiques**: Chart.js 4.x
- **Base de données**: MySQL/MariaDB
- **ORM**: Doctrine
- **Design**: Responsive Design avec approche Mobile-First

---

## Architecture de l'Application

### Structure MVC Symfony

```
bcc-flex/
├── config/              # Configuration Symfony
├── public/              # Assets publics (CSS, JS, images)
│   ├── css/
│   │   └── style.css   # Styles principaux avec responsive
│   └── js/
│       └── app.js      # JavaScript principal
├── src/
│   ├── Controller/     # Contrôleurs
│   ├── Entity/         # Entités Doctrine
│   └── Repository/     # Repositories Doctrine
├── templates/          # Templates Twig
│   ├── base.html.twig
│   ├── dashboard/
│   ├── marche/
│   ├── analyse/
│   ├── finances/
│   └── partials/
└── var/                # Cache et logs
```

### Entités Principales

#### 1. ConjonctureJour
Entité centrale contenant la date de situation pour chaque enregistrement.

**Attributs**:
- `id`: Identifiant unique
- `date_situation`: Date de la situation économique

#### 2. KPIJournalier
Indicateurs clés de performance journaliers.

**Attributs**:
- `cours_indicatif`: Cours de change indicatif BCC
- `parallele_vente`: Cours parallèle vente
- `parallele_achat`: Cours parallèle achat
- `ecart_indic_parallele`: Écart entre indicatif et parallèle
- `reserves_internationales_usd`: Réserves internationales en millions USD
- `solde`: Solde budgétaire
- `conjoncture_id`: Relation vers ConjonctureJour

#### 3. MarcheChanges
Données du marché des changes.

**Attributs**:
- `cours_indicatif`: Cours indicatif
- `parallele_vente`: Cours parallèle vente
- `parallele_achat`: Cours parallèle achat
- `ecart_indic_parallele`: Écart calculé
- `conjoncture`: Relation vers ConjonctureJour

#### 4. FinancesPubliques
Données des finances publiques.

**Attributs**:
- `recettes_fiscales`: Recettes fiscales en milliards CDF
- `autres_recettes`: Autres recettes en milliards CDF
- `recettes_totales`: Total des recettes
- `depenses_totales`: Total des dépenses
- `solde`: Solde budgétaire (recettes - dépenses)
- `conjoncture`: Relation vers ConjonctureJour

#### 5. ReservesFinancieres
Réserves financières de la BCC.

**Attributs**:
- `reserves_internationales_usd`: Réserves internationales en millions USD
- `avoirs_externes_usd`: Avoirs externes en millions USD
- `avoirs_libres_cdf`: Avoirs libres en milliards CDF
- `conjoncture`: Relation vers ConjonctureJour

#### 6. EncoursBcc
Encours des opérations de la BCC.

**Attributs**:
- `encours_ot_bcc`: Encours opérations de trésorerie
- `encours_b_bcc`: Encours bons BCC
- `conjoncture`: Relation vers ConjonctureJour

#### 7. VolumeUSD
Volumes de transactions en USD par banque.

**Attributs**:
- `banque`: Nom de la banque
- `type_transaction`: Type (Achat/Vente)
- `volume_total_usd`: Volume en USD
- `conjoncture`: Relation vers ConjonctureJour

#### 8. PaieEtat
État de la paie.

**Attributs**:
- `montant_total`: Montant total de la paie
- `montant_paye`: Montant déjà payé
- `montant_restant`: Montant restant à payer
- `conjoncture`: Relation vers ConjonctureJour

#### 9. TransactionsUsd
Détail des transactions en USD (table source pour VolumeUSD).

**Attributs**:
- `banque`: Relation vers l'entité Banques
- `type_transaction`: Type (ACHAT/VENTE)
- `cours`: Taux de change de la transaction
- `volume_usd`: Volume en USD
- `conjoncture`: Relation vers ConjonctureJour

### Contrôleurs

#### DashboardController
Route: `/`

**Responsabilités**:
- Afficher la vue d'ensemble avec tous les indicateurs
- Calculer les variations jour à jour
- Préparer les données pour les graphiques

#### MarcheController
Route: `/marche`

**Responsabilités**:
- Afficher les données du marché des changes
- Calculer l'évolution des cours
- Gérer les volumes de transactions

#### AnalyseController
Route: `/analyse`

**Responsabilités**:
- Calculer le score de vigilance économique
- Analyser les indicateurs composites
- Fournir une vue analytique avancée

#### FinancesController
Route: `/finances`

**Responsabilités**:
- Afficher les données des finances publiques
- Gérer la trésorerie et les titres publics
- Suivre l'exécution de la paie

---

## Formules de Calcul des Statistiques

### 1. Dashboard (DashboardController)

#### Variation du Cours Indicatif
```
Variation (%) = ((Cours Actuel - Cours Précédent) / Cours Précédent) × 100
```

**Exemple**:
- Cours actuel: 2850 CDF
- Cours précédent: 2800 CDF
- Variation = ((2850 - 2800) / 2800) × 100 = **1.79%**

#### Variation des Réserves Internationales
```
Variation (%) = ((Réserves Actuelles - Réserves Précédentes) / Réserves Précédentes) × 100
```

**Exemple**:
- Réserves actuelles: 5200 millions USD
- Réserves précédentes: 5000 millions USD
- Variation = ((5200 - 5000) / 5000) × 100 = **4.00%**

#### Variation du Solde Budgétaire
```
Variation = Solde Actuel - Solde Précédent
```

**Exemple**:
- Solde actuel: 150 milliards CDF
- Solde précédent: 100 milliards CDF
- Variation = 150 - 100 = **50 milliards CDF**

### 2. Marché (MarcheController)

#### Variation du Cours Indicatif
```
Variation (%) = ((Cours Latest - Cours Previous) / Cours Previous) × 100
```

Cette formule est identique à celle du Dashboard mais appliquée spécifiquement aux données du marché des changes.

#### Écart Indicatif-Parallèle
```
Écart = Cours Parallèle Vente - Cours Indicatif BCC
```

**Exemple**:
- Cours parallèle vente: 2950 CDF
- Cours indicatif BCC: 2850 CDF
- Écart = 2950 - 2850 = **100 CDF**

### 3. Analyse (Indice de Tension du Marché - ITM)

L'ITM est un indicateur composite dynamique calculé sur une échelle de 0 à 100, conçu pour mesurer l'intensité des tensions sur le marché économique.

#### Principe de Calcul
Le score global est une **moyenne pondérée** des scores individuels de chaque indicateur configuré en base de données.

```
ITM = (Σ (Score_Indicateur × Poids_Indicateur)) / Σ Poids_Total
```

#### Calcul du Score par Indicateur (0-100)
Chaque indicateur est évalué par rapport à deux seuils définis dans la table `regle_intervention` :
1. **Seuil de Vigilance** (correspond à un score de 30)
2. **Seuil d'Intervention** (correspond à un score de 60)

Le calcul dépend du sens de variation défavorable ("hausse" ou "baisse").

**Cas 1 : Sens "Hausse" (ex: Taux de change)**
- Si Valeur ≤ Seuil Vigilance : Score entre 0 et 30 (Zone Normale)
- Si Vigilance < Valeur ≤ Intervention : Score entre 30 et 60 (Zone Vigilance)
- Si Valeur > Intervention : Score entre 60 et 100 (Zone Intervention)

**Cas 2 : Sens "Baisse" (ex: Réserves de change)**
- Si Valeur ≥ Seuil Vigilance : Score entre 0 et 30 (Zone Normale)
- Si Intervention ≤ Valeur < Vigilance : Score entre 30 et 60 (Zone Vigilance)
- Si Valeur < Intervention : Score entre 60 et 100 (Zone Intervention)

#### Classification de l'ITM
- **0 - 30** : 🟢 **NORMAL** (Situation stable)
- **30 - 60** : 🟡 **VIGILANCE** (Surveillance requise)
- **60 - 100** : 🔴 **INTERVENTION** (Action corrective nécessaire)

### 4. Système d'Alertes (AlerteService)

Le système d'alertes surveille quotidiennement chaque indicateur et génère des notifications basées sur les mêmes seuils que l'ITM.

- **Statut NORMAL** : Valeur dans les limites acceptables.
- **Statut VIGILANCE** : Seuil de vigilance franchi.
- **Statut ALERTE** : Seuil d'intervention franchi.

Les alertes sont historisées dans la table `alerte_change` pour permettre un suivi dans le temps.

### 4. Finances (FinancesController)

#### Solde Budgétaire
```
Solde = Recettes Totales - Dépenses Totales
```

**Exemple**:
- Recettes totales: 1200 milliards CDF
- Dépenses totales: 1100 milliards CDF
- Solde = 1200 - 1100 = **100 milliards CDF** (Excédent)

#### Taux d'Exécution de la Paie
```
Taux d'Exécution (%) = (Montant Payé / Montant Total) × 100
```

**Exemple**:
- Montant total: 500 milliards CDF
- Montant payé: 425 milliards CDF
- Taux = (425 / 500) × 100 = **85%**

---

## Guide d'Utilisation

### Navigation

#### Desktop
1. **Sidebar gauche**: Navigation principale entre les différentes sections
2. **Header**: Affiche la date/heure actuelle et bouton de rafraîchissement
3. **Contenu principal**: Graphiques et tableaux de données

#### Mobile
1. **Menu hamburger** (☰): Cliquer pour ouvrir la sidebar
2. **Overlay sombre**: Cliquer pour fermer la sidebar
3. **Swipe gauche**: Glisser vers la gauche sur la sidebar pour la fermer
4. **Navigation**: Cliquer sur un lien ferme automatiquement la sidebar

### Sections de l'Application

#### 1. Tableau de Bord (/)
**Vue d'ensemble complète** avec:
- 5 KPI principaux en haut (Cours Indicatif, Cours Parallèle, Réserves, Solde, Écart)
- Graphique d'évolution des cours (7 jours)
- Répartition des recettes (camembert)
- Volumes USD par banque (barres horizontales)
- Cascade budgétaire (waterfall)
- Évolution des réserves (area chart)
- Exécution de la paie (doughnut)
- Historique des indicateurs (tableau)
- Radar de vigilance

#### 2. Marché (/marche)
**Données du marché des changes** avec:
- Cours indicatif et parallèle
- Volumes de transactions
- Réserves financières
- Encours BCC
- Évolution historique

#### 3. Analyse (/analyse)
**Vue analytique avancée** avec:
- Score de vigilance économique
- 5 indicateurs composites
- Graphiques d'analyse
- Interprétation des tendances

#### 4. Finances (/finances)
**Finances publiques** avec:
- Recettes et dépenses
- Trésorerie de l'État
- Titres publics
- État de la paie

#### 5. Import de Données (/import)
**Module d'administration** permettant de:
- Télécharger des fichiers CSV ou Excel contenant les données de conjoncture
- Prévisualiser les données avant import
- Valider la cohérence des formats
- Mettre à jour automatiquement les indicateurs et recalculer l'ITM

### Interprétation des Indicateurs

#### Codes Couleur
- 🟢 **Vert**: Situation favorable, tendance positive
- 🟡 **Orange**: Situation modérée, vigilance requise
- 🔴 **Rouge**: Situation critique, action nécessaire
- 🔵 **Bleu**: Information neutre

#### Variations
- ↗️ **Flèche montante**: Augmentation
- ↘️ **Flèche descendante**: Diminution

**Attention**: Pour le cours de change, une augmentation (↗️) est négative (dépréciation du CDF).

---

## Guide de Développement

### Installation

#### Prérequis
- PHP 8.2 ou supérieur
- Composer
- MySQL/MariaDB 8.0+
- Node.js (optionnel, pour les assets)

#### Installation
```bash
# Cloner le repository
git clone [url-du-repo]
cd bcc-flex

# Installer les dépendances
composer install

# Configurer la base de données
cp .env .env.local
# Éditer .env.local avec vos paramètres DB

# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Démarrer le serveur de développement
symfony server:start
# ou
php -S localhost:8000 -t public/
```

### Structure du Code

#### Ajouter un Nouveau Contrôleur
```php
<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MonController extends AbstractController
{
    #[Route('/mon-route', name: 'app_mon_route')]
    public function index(): Response
    {
        return $this->render('mon_template/index.html.twig', [
            'data' => $this->getData(),
        ]);
    }
}
```

#### Ajouter une Méthode Repository
```php
public function getCustomData(int $limit = 30): array
{
    return $this->createQueryBuilder('e')
        ->join('e.conjoncture', 'c')
        ->orderBy('c.date_situation', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

#### Créer un Nouveau Graphique Chart.js
```javascript
new Chart(document.getElementById('monGraphique'), {
    type: 'line', // line, bar, pie, doughnut, radar, etc.
    data: {
        labels: ['Jan', 'Fév', 'Mar'],
        datasets: [{
            label: 'Mon Dataset',
            data: [10, 20, 30],
            borderColor: '#3B6FAB',
            backgroundColor: 'rgba(59, 111, 171, 0.1)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
```

### Bonnes Pratiques

#### CSS
- Utiliser les variables CSS définies dans `:root`
- Respecter la convention de nommage BEM
- Tester sur mobile avant desktop (Mobile-First)
- Utiliser les classes utilitaires existantes

#### JavaScript
- Utiliser ES6+ (const, let, arrow functions)
- Commenter les fonctions complexes
- Gérer les erreurs avec try/catch
- Optimiser les performances (debounce, throttle)

#### Twig
- Utiliser l'héritage de templates (`{% extends %}`)
- Créer des partials réutilisables
- Filtrer et échapper les données (`|e`, `|raw`)
- Utiliser les fonctions Twig (`date()`, `number_format()`)

---

## Améliorations Responsive Mobile

### Vue d'Ensemble des Améliorations

L'application a été considérablement améliorée pour offrir une expérience mobile professionnelle et moderne.

### Breakpoints Responsive

#### 5 Niveaux de Breakpoints
```css
/* Mobile Small */
@media (max-width: 480px) { }

/* Mobile Large */
@media (max-width: 768px) { }

/* Tablet */
@media (max-width: 992px) { }

/* Desktop Small */
@media (max-width: 1200px) { }

/* Desktop Large */
/* 1201px+ - Styles par défaut */
```

### Fonctionnalités Mobile

#### 1. Navigation Mobile Professionnelle

**Menu Hamburger**:
- Bouton stylisé avec gradient BCC
- Taille de 44x44px (touch target optimal)
- Animations au hover et au clic
- Visible uniquement sur mobile/tablette

**Sidebar Mobile**:
- Slide-in depuis la gauche avec animation fluide
- Overlay sombre avec backdrop blur
- Fermeture automatique au clic sur un lien
- Support des swipe gestures (glisser vers la gauche)
- Prévention du scroll du body quand ouverte

**Overlay**:
- Fond sombre semi-transparent (rgba(0, 0, 0, 0.6))
- Effet de flou (backdrop-filter: blur(4px))
- Fermeture au clic
- Transition fluide

#### 2. Composants Optimisés

**KPI Cards**:
- Layout vertical sur mobile (1 colonne)
- Tailles réduites mais lisibles
- Icônes et valeurs proportionnelles
- Espacement optimisé

**Tableaux**:
- Scroll horizontal avec smooth scrolling
- Indicateur visuel de scroll (flèche animée →)
- Largeur minimale pour forcer le scroll
- Touch-friendly

**Graphiques**:
- Hauteur réduite sur mobile (220-250px)
- Taille de police adaptée (10px sur mobile)
- Légendes compactes
- Padding réduit

**Header**:
- Hauteur réduite (60-70px selon breakpoint)
- Sous-titre masqué sur mobile
- Éléments empilés si nécessaire
- Boutons de taille optimale

**Stats Header (Dashboard)**:
- Layout vertical sur mobile
- Stat boxes empilées
- Icônes et textes proportionnels
- Espacement réduit

#### 3. Touch Targets

Tous les éléments interactifs respectent la taille minimale de **44x44px** pour une utilisation tactile confortable:
- Boutons
- Liens de navigation
- Bouton refresh
- Menu toggle

#### 4. Optimisations JavaScript

**Swipe Gestures**:
```javascript
// Swipe vers la gauche pour fermer la sidebar
touchStartX → touchEndX
Si distance < -50px → closeSidebar()
```

**Auto-close**:
- Fermeture automatique lors du clic sur un lien de navigation
- Fermeture automatique lors du passage en mode desktop

**Optimisation des Graphiques**:
- Taille de police réduite sur mobile
- Padding et espacement adaptés
- Recalcul lors du redimensionnement (debounced)

#### 5. Landscape Mode

Optimisations spécifiques pour le mode paysage mobile:
- Sidebar de largeur réduite (260px)
- Header compact (60px)
- Grids en 2 colonnes quand possible

### Compatibilité

#### Navigateurs Supportés
- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Opera 76+

#### Appareils Testés
- ✅ iPhone SE (375px)
- ✅ iPhone 12/13/14 (390px)
- ✅ Samsung Galaxy S20/S21 (360px)
- ✅ iPad (768px)
- ✅ iPad Pro (1024px)

### Performances

**Optimisations**:
- Transitions CSS hardware-accelerated
- Debouncing des événements resize
- Passive event listeners pour le scroll
- Lazy loading des graphiques (si implémenté)

**Temps de Chargement**:
- First Contentful Paint: < 1.5s
- Time to Interactive: < 3s
- Lighthouse Mobile Score: 90+

---

## Maintenance et Support

### Logs

Les logs sont disponibles dans:
```
var/log/dev.log  # Environnement de développement
var/log/prod.log # Environnement de production
```

### Cache

Vider le cache:
```bash
php bin/console cache:clear
```

### Mise à Jour

```bash
# Mettre à jour les dépendances
composer update

# Exécuter les nouvelles migrations
php bin/console doctrine:migrations:migrate
```

---

## Annexes

### Glossaire

- **BCC**: Banque Centrale du Congo
- **CDF**: Franc Congolais
- **USD**: Dollar Américain
- **KPI**: Key Performance Indicator (Indicateur Clé de Performance)
- **Cours Indicatif**: Taux de change officiel fixé par la BCC
- **Cours Parallèle**: Taux de change du marché informel
- **Encours**: Montant total des opérations en cours

### Contacts

Pour toute question ou support technique, contacter:
- Email: support@bcc.cd
- Téléphone: +243 XXX XXX XXX

---

**Version**: 1.0.0  
**Date de dernière mise à jour**: 05 Janvier 2026  
**Auteur**: Équipe Développement BCC-Flex
